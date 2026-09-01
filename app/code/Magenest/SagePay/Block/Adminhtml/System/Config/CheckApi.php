<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Adminhtml\System\Config;

class CheckApi extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $sageHelperMoto;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Store\Model\StoreFactory $storeFactory
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magenest\SagePay\Helper\SageHelperMoto $sageHelperMoto,
        array $data = []
    ) {
        $this->sageHelperMoto = $sageHelperMoto;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/check_api.phtml');
        }

        return $this;
    }

    /**
     * Before rendering html, but after trying to load cache
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->addData(
            [
                'add_class' => ("btn-success"),
                'button_label' => __("Check Api"),
                'html_id' => "check_sage_api_button",
            ]
        );

        return $this->_toHtml();
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        $pageParams = $this->_request->getParams();
        if (isset($pageParams['key'])) {
            unset($pageParams['key']);
        }
        return parent::getUrl($route, array_merge($pageParams, $params));
    }
}
