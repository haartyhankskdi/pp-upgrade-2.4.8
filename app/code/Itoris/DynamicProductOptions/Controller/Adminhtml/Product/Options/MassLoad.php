<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_DYNAMIC_PRODUCT_OPTIONS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

namespace Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options;

class MassLoad extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $productIds = $this->getRequest()->getParam('product_ids');
        if (!$productIds) {
            $filter = $this->_objectManager->create('Magento\Ui\Component\MassAction\Filter');
            $collectionFactory = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
            $collection = $filter->getCollection($collectionFactory->create());
            $productIds = $collection->getAllIds();
            $silenceMode = false;
        } else $silenceMode = true;
        
        $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('read');
        $method = (int) $this->getRequest()->getParam('method');
        $_templateId = (int) $this->getRequest()->getParam('template_id');
        
        if (is_array($productIds)) {
            try {
                $templateProto = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')->load($_templateId);
                $sectionsProto = json_decode($templateProto->getConfiguration(), true);
                
                if ($templateProto->getId()) {
                    if ($method == 2 || $method == 3) {
                        foreach($sectionsProto as $key => $section) {
                            if (is_array($section)) $sectionsProto[$key]['template_id'] = $_templateId;
                        }
                        $templateProto->setConfiguration(json_encode($sectionsProto));
                    }

                    $saved = 0;
                    
                    //store configs
                    $storeConfigs = [0 => ['template' => $templateProto, 'sections' => $sectionsProto]];
                    $templateIds = $con->fetchCol("select `template_id` from {$res->getTableName('itoris_dynamicproductoptions_template')} where `parent_id`={$templateProto->getId()}");
                    foreach($templateIds as $templateId) {
                        $_templateProto = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')->load($templateId);
                        $_sectionsProto = json_decode($_templateProto->getConfiguration(), true); 
                        if ($method == 2 || $method == 3) {
                            foreach($_sectionsProto as $key => $section) {
                                if (is_array($section)) $_sectionsProto[$key]['template_id'] = $_templateId;
                            }
                            $_templateProto->setConfiguration(json_encode($_sectionsProto));
                        }
                        $storeConfigs[$_templateProto->getStoreId()] = ['template' => $_templateProto, 'sections' => $_sectionsProto];
                    }
                    
                    foreach ($productIds as $newProductId) {
                        $finalConfig = [];
                        $_storeConfigs = $storeConfigs;
                        
                        if ($method == 1 || $method == 3) {
                            $_productConfigs = $con->fetchAll("select * from {$res->getTableName('itoris_dynamicproductoptions_options')} where `product_id`={$newProductId} order by `store_id` asc");
                            if (!count($_productConfigs)) {
                                //no DPO object for the product yet, checking for existing options
                                $dpoObject = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options');
                                $dpoObject->setProductId($newProductId)->setStoreId(0);
                                $_sections = $dpoObject->getSections();
                                if (count($_sections) && count($_sections[0]['fields'])) {
                                    array_unshift($_sections[0]['fields'], null);
                                    foreach($_sections[0]['fields'] as $key => $_field) if ($_field !== null) $_sections[0]['fields'][$key]['internal_id'] = $key;
                                    $_productConfigs = [[
                                        'store_id' => 0,
                                        'form_style' => 'table_sections',
                                        'appearance' => 'on_product_view',
                                        'css_adjustments' => '',
                                        'extra_js' => '',
                                        'absolute_pricing' => 0,
                                        'absolute_sku' => 0,
                                        'absolute_weight' => 0,
                                        'product_id' => $newProductId,
                                        'configuration' => json_encode($_sections)
                                    ]];  
                                }                                
                            }
                            foreach($_productConfigs as $_productConfig) {
                                $template = new \Magento\Framework\DataObject();
                                $template->setData($_productConfig);
                                $template['sections'] = (array) json_decode($_productConfig['configuration'], true);
                                $finalConfig[(int)$_productConfig['store_id']] = $template;
                            }
                            foreach($finalConfig as $storeId => $_finalConfig) {
                                if (!isset($_storeConfigs[$storeId])) $_storeConfigs[$storeId] = $_storeConfigs[0];
                            }
                        }

                        foreach($_storeConfigs as $storeId => $storeConfig) {
                            $template = new \Magento\Framework\DataObject();
                            $template->setData($storeConfig['template']->getData());

                            if ($method == 1 || $method == 3) { //append options
                                if (isset($finalConfig[$storeId])) {
                                    $config = $finalConfig[$storeId]->getData();
                                    $_template = $finalConfig[$storeId]['sections'];
                                } else {
                                    $config = $con->fetchRow("select * from {$res->getTableName('itoris_dynamicproductoptions_options')} where `product_id`={$newProductId} and `store_id`={$storeId}");
                                    $_template = (array) json_decode($config['configuration'], true);
                                    if (!count($_template) && isset($finalConfig[0])) {
                                        $config = $finalConfig[0]->getData();
                                        $_template = $finalConfig[0]['sections'];
                                    }
                                }
                                
                                $maxInternalId = 0;
                                $sectionOrder = -1;
                                $templateIdsInConfig = [$_templateId => 1];
                                $templatePrevOrder = -1;
                                $sections = $storeConfig['sections'];

                                foreach($_template as $key => $_s) {
                                    $sectionOrder++;
                                    if (isset($_s['template_id'])) $templateIdsInConfig[$_s['template_id']] = 1;
                                    if (!isset($_s['fields']) || !is_array($_s['fields'])) continue;
                                    if (isset($_s['template_id']) && (int) $_s['template_id'] == $_templateId) {
                                        if ($templatePrevOrder == -1) $templatePrevOrder = $key;
                                        unset($_template[$key]);
                                        $sectionOrder--;
                                        continue;
                                    }
                                    if ($templatePrevOrder == -1) {
                                        $_template[$key]['order'] = $sectionOrder;
                                        foreach($_s['fields'] as $key2 => $_field) {
                                            if (!is_array($_field)) continue;
                                            $_template[$key]['fields'][$key2]['section_order'] = $sectionOrder;
                                            if (isset($_field['internal_id']) && $_field['internal_id'] > $maxInternalId) $maxInternalId = $_field['internal_id'];
                                        }
                                    } else {
                                        $sections[] = $_s;
                                        unset($_template[$key]);
                                        $sectionOrder--;                                        
                                    }
                                }
                                $_template = array_values($_template);

                                foreach($sections as $section) if (is_array($section)) {
                                    $section['order'] = count($_template);
                                    foreach($section['fields'] as &$field) {
                                        if (isset($field['internal_id'])) $field['internal_id'] += $maxInternalId;
                                        if (isset($field['section_order'])) $field['section_order'] = $section['order'];
                                        if (isset($field['visibility_condition']) && $field['visibility_condition']) {
                                            $_condition = json_decode($field['visibility_condition'], true);
                                            if ($_condition && isset($_condition['conditions']) && is_array($_condition['conditions'])) {
                                                foreach($_condition['conditions'] as $key => $condition) {
                                                    if (isset($_condition['conditions'][$key]['field'])) $_condition['conditions'][$key]['field'] += $maxInternalId;
                                                }
                                                $field['visibility_condition'] = json_encode($_condition);
                                            }
                                        }
										if (isset($field['items']) && is_array($field['items'])) {
											foreach($field['items'] as &$item) {
												if (!is_array($item)) continue;
												if (isset($item['visibility_condition']) && $item['visibility_condition']) {
													$_condition = json_decode($item['visibility_condition'], true);
													if ($_condition && isset($_condition['conditions']) && is_array($_condition['conditions'])) {
														foreach($_condition['conditions'] as $key => $condition) {
															if (isset($_condition['conditions'][$key]['field'])) $_condition['conditions'][$key]['field'] += $maxInternalId;
														}
														$item['visibility_condition'] = json_encode($_condition);
													}
												}
											}
										}
                                    }
                                    if (isset($section['visibility_condition']) && $section['visibility_condition']) {
                                        $_condition = json_decode($section['visibility_condition'], true);
                                        if ($_condition && isset($_condition['conditions']) && is_array($_condition['conditions'])) {
                                            foreach($_condition['conditions'] as $key => $condition) {
                                                if (isset($_condition['conditions'][$key]['field'])) $_condition['conditions'][$key]['field'] += $maxInternalId;
                                            }
                                            $section['visibility_condition'] = json_encode($_condition);
                                        }
                                    }
                                    $_template[$section['order']] = $section;
                                }
                                
                                $templateIdsInConfig = array_keys($templateIdsInConfig);

                                $template->setConfiguration(json_encode($_template));
                                $template->setSections($_template);
                                if ($template->getFormStyle() != $config['form_style']) $template->setData('form_style', 'table_sections');
                                $template->setData('appearance', $config['appearance']);
                                $template->setData('absolute_pricing', $config['absolute_pricing']);
                                $template->setData('absolute_sku', $config['absolute_sku']);
                                $template->setData('absolute_weight', $config['absolute_weight']);
                                if (count($templateIdsInConfig)) {
                                    if ($storeId) {
                                        $_js_css = $con->fetchAll("select `parent_id` as `template_id`, `css_adjustments`, `extra_js` from {$res->getTableName('itoris_dynamicproductoptions_template')} where `parent_id` in (".implode(',', $templateIdsInConfig).") and `store_id`={$storeId}");
                                    } else {
                                        $_js_css = $con->fetchAll("select `template_id`, `css_adjustments`, `extra_js` from {$res->getTableName('itoris_dynamicproductoptions_template')} where `template_id` in (".implode(',', $templateIdsInConfig).")");
                                    }
                                    $_js = ''; $_css = '';
                                    foreach($_js_css as $index => $jc) {
                                        if (trim($jc['css_adjustments'])) $_css .= "/* CSS from Template #{$jc['template_id']} */\n".trim($jc['css_adjustments']).($index < count($_js_css) - 1 ? "\n\n" : '');
                                        if (trim($jc['extra_js'])) $_js .= "/* JS from Template #{$jc['template_id']} */\n".trim($jc['extra_js']).($index < count($_js_css) - 1 ? "\n\n" : '');
                                    }
                                    $template->setData('css_adjustments', $_css);
                                    $template->setData('extra_js', $_js);
                                }
                            }
                            $finalConfig[$storeId] = $method == 1 || $method == 3 ? $template : $storeConfig['template'];
                        }
                        //print_r($finalConfig); exit;
                        if ($this->applyToProduct($newProductId, $finalConfig)) {
                            $saved++;
                        }
                    }
                    if (!$silenceMode) {
                        $this->messageManager->addSuccess(__('%1 products have been changed', $saved));
                        
                        //invalidate FPC
                        $cacheTypeList = $this->_objectManager->create('\Magento\Framework\App\Cache\TypeList');
                        $cacheTypeList->invalidate('full_page');
                    }
                    
                } else {
                    if ($silenceMode) {
                        //template deleted, updating related products
                        foreach ($productIds as $productId) {
                            $this->_objectManager->get('Itoris\DynamicProductOptions\Model\Rewrite\Option')->duplicate($productId, $productId);
                        }
                    } else $this->messageManager->addError(__('Template has not been loaded'));
                }
            } catch (\Exception $e) {
                if ($silenceMode) return __('Products have not been changed'); else $this->messageManager->addError(__('Products have not been changed'));
            }
        } else {
            if ($silenceMode) return __('Please select product ids'); else $this->messageManager->addError(__('Please select product ids'));
        }

        if (!$silenceMode) $this->_redirect('catalog/product/', ['_current' => true]);
    }
}