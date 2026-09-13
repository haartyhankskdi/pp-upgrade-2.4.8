<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Answser;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Api\FormRepositoryInterface;
use Amasty\Customform\Helper\Data;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class InformationDataProvider
{
    /**
     * @var FormRepositoryInterface
     */
    private $formRepository;

    /**
     * @var Data
     */
    private $moduleHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    public function __construct(
        FormRepositoryInterface $formRepository,
        Data $moduleHelper,
        StoreManagerInterface $storeManager,
        Escaper $escaper,
        ManagerInterface $eventManager,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->formRepository = $formRepository;
        $this->moduleHelper = $moduleHelper;
        $this->storeManager = $storeManager;
        $this->escaper = $escaper;
        $this->eventManager = $eventManager;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    public function getInformationData(AnswerInterface $answer): array
    {
        $result = [
            ['label' => __('Form'), 'value' => $this->getFormName($answer)],
            ['label' => __('Submitted'), 'value' => $answer->getCreatedAt()],
            AnswerInterface::UPDATED_AT => ['label' => __('Updated'), 'value' => $answer->getUpdatedAt()],
            ['label' => __('IP'), 'value' => $answer->getIp()],
            ['label' => __('Customer'), 'value' => $this->getCustomerName($answer)],
            ['label' => __('Store'), 'value' => $this->getStoreName($answer)]
        ];

        if ($answer->getRefererUrl()) {
            $result[] = ['label' => __('Referrer URL'), 'value' => $this->escaper->escapeUrl($answer->getRefererUrl())];
        }

        if ($answer->getUpdatedAt() === null) {
            unset($result[AnswerInterface::UPDATED_AT]);
        }

        $transportObject = $this->dataObjectFactory->create(['data' => $result]);
        $this->eventManager->dispatch(
            'amasty_customform_collect_answer_information',
            [
                'data_model' => $transportObject,
                'answer' => $answer
            ]
        );

        return $transportObject->getData();
    }

    private function getCustomerName(AnswerInterface $answer): string
    {
        $customerName = $this->moduleHelper->getCustomerName($answer->getCustomerId(), true);

        if (empty($customerName['customer_link'])) {
            $customerName = $customerName['customer_name'];
        } else {
            $customerName = $customerName['customer_link'];
        }

        return (string) $customerName;
    }

    private function getForm(AnswerInterface $answer): ?FormInterface
    {
        try {
            $form = $this->formRepository->get($answer->getFormId());
        } catch (\Exception $ex) {
            $form = null;
        }

        return $form;
    }

    private function getStoreName(AnswerInterface $answer): string
    {
        return $this->storeManager->getStore($answer->getStoreId())->getName();
    }

    private function getFormName(AnswerInterface $answer): string
    {
        $form = $this->getForm($answer);
        switch (true) {
            case $form:
                $formName = $form->getCode();
                break;
            case $answer->getFormCode():
                $formName = $answer->getFormCode() . __('  (removed)');
                break;
            default:
                $formName = __('This form #%1 was removed', $answer->getFormId());
        }

        return (string) $formName;
    }
}
