<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Block;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Helper\Data;
use Amasty\Customform\Model\Config\Source\DateFormat;
use Amasty\Customform\ViewModel\Form\FormInit\AnswerModeFactory;
use Amasty\Customform\ViewModel\Form\FormInitInterface;
use Magento\Backend\Block\Widget\Grid\Column\Filter\Store;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

class Form extends Template implements IdentityInterface
{
    /**
     * @var string
     */
    protected $_template = 'Amasty_Customform::init.phtml';

    /**
     * @var \Amasty\Customform\Helper\Data
     */
    private $helper;

    /**
     * @var array
     */
    private $additionalClasses = [];

    /**
     * @var AnswerModeFactory
     */
    private $viewModelFactory;

    /**
     * @var Context
     */
    private $httpContext;

    public function __construct(
        Template\Context $context,
        Data $helper,
        AnswerModeFactory $viewModelFactory,
        Context $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helper = $helper;
        $this->viewModelFactory = $viewModelFactory;
        $this->httpContext = $httpContext;
    }

    private function init()
    {
        $this->addAdditionalClass($this->getCurrentForm()->getDesignClass());
    }

    /**
     * @return \Amasty\Customform\Helper\Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if ($this->validate()) {
            $this->init();
            return parent::toHtml();
        }

        return '';
    }

    /**
     * @return bool
     * @throws NoSuchEntityException
     */
    protected function validate()
    {
        $form = $this->getCurrentForm();

        if (!$form || !$form->isEnabled()) {
            return false;
        }

        if (!$form->getIsVisible()) {
            return false;
        }

        /* check for store ids*/
        $stores = $form->getStoreId();
        $stores = explode(',', $stores);
        $currentStoreId = $this->_storeManager->getStore()->getId();

        if (!in_array(Store::ALL_STORE_VIEWS, $stores) && !in_array($currentStoreId, $stores)) {
            return false;
        }

        /* check for customer groups*/
        $availableGroups = $form->getCustomerGroup();
        $availableGroups = explode(',', $availableGroups);
        $currentGroup = $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);

        return in_array($currentGroup, $availableGroups)
            || in_array(GroupInterface::CUST_GROUP_ALL, $availableGroups);
    }

    public function getCurrentForm(): ?FormInterface
    {
        return $this->getViewModel()->getCurrentForm();
    }

    /**
     * @return string
     */
    public function getFormDataJson()
    {
        $viewModel = $this->getViewModel();
        $form = $viewModel->getCurrentForm();
        $formData = $form->getFormJson();
        $formTitles = $form->getFormTitle();

        $result = [
            'dataType' => 'json',
            'formData' => $formData,
            'src_image_progress' => $this->getViewFileUrl('Amasty_Customform::images/loading.gif'),
            'ajax_submit' => $this->getCurrentForm()->getSuccessUrl() == Data::REDIRECT_PREVIOUS_PAGE ? 1 : 0,
            'pageTitles' => $formTitles,
            'submitButtonTitle' => $this->_escaper->escapeHtml($viewModel->getButtonTitle()),
            'dateFormat' => $this->helper->getDateFormat(),
            'placeholder' => DateFormat::FORMATS[$this->helper->getDateFormat()]['label'] ?? 'mm/dd/yy'
        ];

        return $this->helper->encode($result);
    }

    /**
     * @return string
     */
    public function getAdditionalClasses()
    {
        return implode(' ', $this->additionalClasses);
    }

    /**
     * @param string $class
     *
     * @return $this
     */
    public function addAdditionalClass($class)
    {
        $this->additionalClasses[] = $class;

        return $this;
    }

    public function getViewModel(): FormInitInterface
    {
        $viewModel = $this->getData('view_model');

        if (!$viewModel) {
            $viewModel = $this->viewModelFactory->create([
                'formId' => (int) $this->getData('form_id'),
                'popup' => $this->getData('popup') === null ? null : (bool) $this->getData('popup'),
                'buttonText' => $this->getData('button_text'),
            ]);
            $this->setData('view_model', $viewModel);
        }

        return $viewModel;
    }

    public function getIdentities(): array
    {
        $form = $this->getViewModel()->getCurrentForm();

        return $form ? $form->getIdentities() : [];
    }
}
