<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Rule\Block\Actions;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Model\Banner as BannerModel;

/**
 * Class ProductConditions
 *
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render
 */
class ProductConditions extends AbstractElement
{
    /**
     * @var Actions
     */
    protected $actions;

    /**
     * @var BannerModel
     */
    protected $bannerModel;

    /**
     * @var Text
     */
    protected $input;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * ProductConditions constructor.
     *
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param Actions $actions
     * @param BannerModel $bannerModel
     * @param Registry $registry
     * @param Data $helperData
     * @param array $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        Actions $actions,
        BannerModel $bannerModel,
        Registry $registry,
        Data $helperData,
        array $data = []
    ) {
        $this->actions     = $actions;
        $this->bannerModel = $bannerModel;
        $this->registry    = $registry;
        $this->helperData  = $helperData;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $model = $this->registry->registry('mppromobanner_banner');
        if ($model) {
            $this->bannerModel->loadPost($model->getData());
        }
    }

    /**
     * @param RendererInterface $renderer
     *
     * @return AbstractElement
     */
    public function setRenderer(RendererInterface $renderer)
    {
        $this->bannerModel->getActions()->setJsFormObject($this->getHtmlId());

        return parent::setRenderer($renderer);
    }

    /**
     * @return string
     */
    public function getElementHtml()
    {
        $htmlId      = $this->getHtmlId();
        $newChildUrl = $this->_escaper->escapeUrl($this->getNewChildUrl());

        $html = '<div class="control admin__field-control" id="' . $this->getHtmlId() . '">
        <div class="rule-tree">
            <div class="rule-tree-wrapper">
                    ' . $this->getInputHtml() . '
            </div>
        </div>
    </div>';

        $html .= '<script>
    require([
        "Magento_Rule/rules",
        "prototype"
    ], function(VarienRulesForm){
        window.' . $htmlId . ' = new VarienRulesForm("' . $htmlId . '", "' . $newChildUrl . '");
    });
</script>';

        return $html;
    }

    /**
     * @return string
     */
    public function getInputHtml()
    {
        $this->input = $this->_factoryElement->create('text');
        $this->input->setRule($this->bannerModel)->setRenderer($this->actions);

        return $this->input->toHtml();
    }
}
