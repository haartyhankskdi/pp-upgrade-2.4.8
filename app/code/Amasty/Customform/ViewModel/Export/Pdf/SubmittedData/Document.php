<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Export\Pdf\SubmittedData;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\Config\Source\Status;
use Amasty\Customform\Model\Submit;
use Amasty\Customform\ViewModel\Answser\InformationDataProvider;
use Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\Document\IsCanRenderFieldInterface;
use Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\Fields\FieldValueModelFactory;
use Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\Fields\TemplateGenerator;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Header\Logo;

class Document implements ArgumentInterface
{
    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * @var FieldValueModelFactory
     */
    private $fieldValueModelFactory;

    /**
     * @var TemplateGenerator
     */
    private $templateGenerator;

    /**
     * @var AnswerInterface
     */
    private $answer;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Logo
     */
    private $magentoLogo;

    /**
     * @var InformationDataProvider
     */
    private $informationDataProvider;

    /**
     * @var Emulation
     */
    private $environmentEmulation;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var IsCanRenderFieldInterface
     */
    private $isCanRenderFieldChecker;

    public function __construct(
        BlockFactory $blockFactory,
        FieldValueModelFactory $fieldValueModelFactory,
        TemplateGenerator $templateGenerator,
        Serializer $serializer,
        Logo $magentoLogo,
        InformationDataProvider $informationDataProvider,
        Emulation $environmentEmulation,
        StoreManagerInterface $storeManager,
        IsCanRenderFieldInterface $isCanRenderFieldChecker
    ) {
        $this->blockFactory = $blockFactory;
        $this->fieldValueModelFactory = $fieldValueModelFactory;
        $this->templateGenerator = $templateGenerator;
        $this->serializer = $serializer;
        $this->magentoLogo = $magentoLogo;
        $this->informationDataProvider = $informationDataProvider;
        $this->environmentEmulation = $environmentEmulation;
        $this->storeManager = $storeManager;
        $this->isCanRenderFieldChecker = $isCanRenderFieldChecker;
    }

    public function setAnswer(AnswerInterface $answer)
    {
        $this->answer = $answer;
    }

    public function getAnswer(): AnswerInterface
    {
        if ($this->answer === null) {
            throw new LocalizedException(__('Answer model was not set'));
        }

        return $this->answer;
    }

    /**
     * @return BlockInterface[]
     * @throws LocalizedException
     */
    public function getFieldsBlocks(): iterable
    {
        $fieldsConfig = $this->getFieldsConfig();

        foreach ($fieldsConfig as $fieldConfig) {
            if (!empty($fieldConfig[Submit::TYPE]) && isset($fieldConfig[Submit::VALUE])) {
                if ($this->isCanRenderFieldChecker->isCanRender($fieldConfig)) {
                    $type = (string)$fieldConfig[Submit::TYPE];
                    $viewModel = $this->fieldValueModelFactory->create($type);
                    $viewModel->setFieldValue($fieldConfig[Submit::VALUE]);
                    /** @var Template $block * */
                    $block = $this->blockFactory->createBlock(
                        Template::class,
                        ['data' => ['view_model' => $viewModel]]
                    );
                    $block->setTemplate($this->templateGenerator->generate($type));

                    yield $fieldConfig[Submit::LABEL] ?? '' => $block;
                }
            }
        }
    }

    private function getFieldsConfig(): array
    {
        $answer = $this->getAnswer();
        $submittedData = $answer->getResponseJson();

        try {
            $result = $this->serializer->unserialize($submittedData);
        } catch (\Exception $e) {
            $result = [];
        }

        return $result;
    }

    public function getLogoSrc(): string
    {
        try {
            $storeId = (int) $this->getAnswer()->getStoreId();
            $this->storeManager->getStore($storeId);
        } catch (NoSuchEntityException $e) {
            $storeId = Store::DEFAULT_STORE_ID;
        }

        $this->environmentEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        $logoSrc = $this->magentoLogo->getLogoSrc();
        $this->environmentEmulation->stopEnvironmentEmulation();

        return $logoSrc;
    }

    public function getFormInformationData(): array
    {
        return $this->informationDataProvider->getInformationData($this->getAnswer());
    }

    public function getAdminResponseData(): array
    {
        $answer = $this->getAnswer();
        $adminResponseStatus = $answer->getAdminResponseStatus() == Status::ANSWERED ? __('Sent') : __('Pending');
        $result = [
            __('Response Status')->render() => $adminResponseStatus
        ];

        if ($answer->getAdminResponseStatus() == Status::ANSWERED) {
            $result[__('Response')->render()] = $answer->getResponseMessage();
        }

        return $result;
    }
}
