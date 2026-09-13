<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Form\FormInit;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Api\Data\FormInterfaceFactory;
use Amasty\Customform\Exceptions\FormRenderingException;
use Amasty\Customform\Helper\Data as ModuleHelper;
use Amasty\Customform\Model\CachingFormProvider;
use Amasty\Customform\Model\ConfigProvider;
use Amasty\Customform\Model\SurveyAvailableResolver;
use Amasty\Customform\ViewModel\Form\FormInitInterface;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AnswerMode implements FormInitInterface
{
    public const DEFAULT_FORM_FILLER = 'Amasty_Customform/js/form-filler';

    /**
     * @var int
     */
    private $formId;

    /**
     * @var bool
     */
    private $isUseGoogleMap = false;

    /**
     * @var FormInterface
     */
    private $currentForm;

    /**
     * @var FormInterfaceFactory
     */
    private $formFactory;

    /**
     * @var ModuleHelper
     */
    private $formHelper;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Context
     */
    private $httpContext;

    /**
     * @var CachingFormProvider
     */
    private $cachingFormProvider;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var SurveyAvailableResolver
     */
    private $surveyAvailableResolver;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var bool|null
     */
    private $popup;

    /**
     * @var string|null
     */
    private $buttonText;

    public function __construct(
        FormInterfaceFactory $formFactory,
        ModuleHelper $formHelper,
        ConfigProvider $configProvider,
        FormKey $formKey,
        Serializer $serializer,
        Escaper $escaper,
        UrlInterface $urlBuilder,
        Context $httpContext,
        CachingFormProvider $cachingFormProvider,
        DataObjectFactory $dataObjectFactory,
        ManagerInterface $eventManager,
        SurveyAvailableResolver $surveyAvailableResolver,
        Registry $registry,
        int $formId,
        ?bool $popup = null,
        ?string $buttonText = null
    ) {
        $this->formFactory = $formFactory;
        $this->formHelper = $formHelper;
        $this->configProvider = $configProvider;
        $this->formKey = $formKey;
        $this->serializer = $serializer;
        $this->escaper = $escaper;
        $this->urlBuilder = $urlBuilder;
        $this->httpContext = $httpContext;
        $this->cachingFormProvider = $cachingFormProvider;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->eventManager = $eventManager;
        $this->surveyAvailableResolver = $surveyAvailableResolver;
        $this->registry = $registry;
        $this->formId = $formId;
        $this->popup = $popup;
        $this->buttonText = $buttonText;
    }

    public function getFormId(): int
    {
        if ($this->formId === null) {
            throw new FormRenderingException(__('View model wasn\'t initialized')->render());
        }

        return $this->formId;
    }

    public function getCurrentForm(): ?FormInterface
    {
        if ($this->currentForm === null) {
            try {
                $this->currentForm = $this->cachingFormProvider->getById((int) $this->getFormId());

                if ($this->currentForm) {
                    $this->updateFormInfo($this->currentForm);
                }
            } catch (\Throwable $e) {
                $this->currentForm = null;
            }
        }

        return $this->currentForm;
    }

    public function isSurvey(): bool
    {
        return (bool) $this->getCurrentForm()->isSurveyModeEnabled();
    }

    public function getFormAction(): string
    {
        return $this->formHelper->getSubmitUrl();
    }

    public function getGDPRText(): string
    {
        return $this->configProvider->getGdprText();
    }

    public function isUseGoogleMap(): bool
    {
        return $this->isUseGoogleMap;
    }

    public function setUseGoogleMap(bool $useGoogleMap): void
    {
        $this->isUseGoogleMap = $useGoogleMap;
    }

    public function getGoogleKey(): string
    {
        return $this->configProvider->getGoogleKey();
    }

    public function getPopupButtonTitle(): string
    {
        return $this->buttonText === null ? strip_tags($this->getCurrentForm()->getPopupButton()) : $this->buttonText;
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    private function getSessionUrl(): string
    {
        return $this->urlBuilder->getUrl('amasty_customform/form/sessiondata');
    }

    public function getFormParamsJson(): string
    {
        $product = $this->registry->registry('current_product');

        return $this->serializer->serialize([
            'urlSession' => $this->escaper->escapeUrl($this->getSessionUrl()),
            'formId' => $this->getFormId(),
            'productId' => $product ? $product->getId() : 0
        ]);
    }

    public function getButtonTitle(): string
    {
        $title = $this->getCurrentForm()->getSubmitButton() ?: __('Submit');

        return (string) $title;
    }

    public function isPopupUsed(): bool
    {
        return $this->popup === null
            ? $this->getCurrentForm()->isPopupShow()
            : $this->popup;
    }

    public function getFormFillerComponent(): string
    {
        return self::DEFAULT_FORM_FILLER;
    }

    public function isGDPREnabled(?int $storeId = null): bool
    {
        return $this->formHelper->isGDPREnabled();
    }

    public function updateFormInfo(FormInterface $form)
    {
        $formData = $form->getFormJson();
        $formData = $this->serializer->unserialize($formData);
        $updatedFormData = [];

        foreach ($formData as $page) {
            $updatedFormData[] = $this->dataProcessing($page);
        }

        $form->setFormJson(
            $this->serializer->serialize(array_values($updatedFormData))
        );
    }

    private function dataProcessing(array $page): array
    {
        $processedPage = [];

        if (isset($page['type'])) {
            // support for old versions of forms
            $processedPage = $this->processElement($page);
        } else {
            foreach ($page as $element) {
                if (!$this->isNeedToHideElement($element)) {
                    $processedPage[] = $this->processElement($element);
                }
            }
        }

        return $processedPage;
    }

    private function isNeedToHideElement(array $element): bool
    {
        return $element['name'] === $this->getCurrentForm()->getEmailField()
            && $this->isCustomerLoggedIn()
            && $this->getCurrentForm()->isHideEmailField();
    }

    private function isCustomerLoggedIn(): bool
    {
        return (bool) $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    private function processElement(array $element): array
    {
        $element['validation_fields'] = $this->formHelper->generateValidation($element);

        if ($element['type'] == 'googlemap') {
            $this->setUseGoogleMap(true);
        }

        return $element;
    }

    public function getGdprCheckboxHtml(string $scope): string
    {
        $checkboxHtml = '';
        $form = $this->getCurrentForm();

        if ($this->surveyAvailableResolver->isSurveyAvailable((int) $form->getFormId())) {
            $result = $this->dataObjectFactory->create();
            $this->eventManager->dispatch(
                'amasty_gdpr_get_checkbox',
                [
                    'scope' => $scope,
                    'result' => $result
                ]
            );
            $checkboxHtml = $result->getData('html') ?: '';
        }

        return $checkboxHtml;
    }
}
