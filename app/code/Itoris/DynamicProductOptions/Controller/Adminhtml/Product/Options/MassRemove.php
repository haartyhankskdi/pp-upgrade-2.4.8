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

class MassRemove extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $filter = $this->_objectManager->create('Magento\Ui\Component\MassAction\Filter');
        $collectionFactory = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $collection = $filter->getCollection($collectionFactory->create());
        $productIds = $collection->getAllIds();

        if (is_array($productIds)) {
            
            //loading configs for all stores
            $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $con = $res->getConnection('read');
            
            foreach ($productIds as $productId) {
                $productData = $con->fetchRow("select * from {$res->getTableName('catalog_product_entity')} where `entity_id`={$productId}");
                
                //check for M2 EE constraint
                if (isset($productData['row_id'])) $dummyId = $productData['row_id']; else $dummyId = $productId;

                //clean all options in magento tables
                $con->delete($res->getTableName('catalog_product_option'), $con->quoteInto('product_id=?', $dummyId)); // row_id for EE, entity_id for CE

                //clean all options in dynamic product options tables
                $con->delete($res->getTableName('itoris_dynamicproductoptions_option'), $con->quoteInto('product_id=?', $productId)); // entity_id for CE and EE
                $con->delete($res->getTableName('itoris_dynamicproductoptions_options'), $con->quoteInto('product_id=?', $productId)); // entity_id for CE and EE
            }            
            
            $this->messageManager->addSuccess(__('%1 products have been changed', count($productIds)));
            
            //invalidate FPC
            $cacheTypeList = $this->_objectManager->create('\Magento\Framework\App\Cache\TypeList');
            $cacheTypeList->invalidate('full_page');
                
        } else {
            $this->messageManager->addError(__('Please select product ids'));
        }

        $this->_redirect('catalog/product/', ['_current' => true]);
    }
}