<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Cron;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Api\FormRepositoryInterface;
use Amasty\Customform\Model\Form;
use Amasty\Customform\Model\Form\Save\Preparation\PrepareActiveDateRanges;
use Amasty\Customform\Model\ResourceModel\Form\Collection as FormCollection;
use Amasty\Customform\Model\ResourceModel\Form\CollectionFactory as FormCollectionFactory;

class ProcessFormScheduling
{
    /**
     * @var FormCollectionFactory
     */
    private $formCollectionFactory;

    /**
     * @var FormRepositoryInterface
     */
    private $formRepository;

    public function __construct(
        FormCollectionFactory $formCollectionFactory,
        FormRepositoryInterface $formRepository
    ) {
        $this->formCollectionFactory = $formCollectionFactory;
        $this->formRepository = $formRepository;
    }

    public function execute(): void
    {
        /** @var FormInterface $form **/
        foreach ($this->getFormsToEnable() as $form) {
            $form->setIsVisible(PrepareActiveDateRanges::VISIBLE_VALUE);
            $this->formRepository->save($form);
        }

        /** @var FormInterface $form **/
        foreach ($this->getOutdatedFormsCollection() as $form) {
            $form->setIsVisible(PrepareActiveDateRanges::INVISIBLE_VALUE);
            $this->formRepository->save($form);
        }
    }

    private function getOutdatedFormsCollection(): FormCollection
    {
        $collection = $this->getNewCollection();
        $collection->addFieldToFilter(
            FormInterface::IS_VISIBLE,
            ['eq' => (int) PrepareActiveDateRanges::VISIBLE_VALUE]
        );
        $collection->addFieldToFilter(
            FormInterface::SCHEDULED_TO,
            ['notnull' => true]
        );
        $collection->addFieldToFilter(
            FormInterface::SCHEDULED_TO,
            ['lt' => new \Zend_Db_Expr('NOW()')]
        );

        return $collection;
    }

    private function getFormsToEnable(): FormCollection
    {
        $collection = $this->getNewCollection();
        $collection->addFieldToFilter(
            FormInterface::IS_VISIBLE,
            ['eq' => (int) PrepareActiveDateRanges::INVISIBLE_VALUE]
        );
        $collection->addFieldToFilter(
            [
                'is_null' => FormInterface::SCHEDULED_FROM,
                'lower_than_now' => FormInterface::SCHEDULED_FROM
            ],
            [
                'lower_than_now' => ['lt' => new \Zend_Db_Expr('NOW()')],
                'is_null' => ['null' => true]
            ]
        );
        $collection->addFieldToFilter(
            [
                'is_null' => FormInterface::SCHEDULED_TO,
                'greater_than_now' => FormInterface::SCHEDULED_TO
            ],
            [
                'greater_than_now' => ['gt' => new \Zend_Db_Expr('NOW()')],
                'is_null' => ['null' => true]
            ]
        );

        return $collection;
    }

    private function getNewCollection(): FormCollection
    {
        $collection = $this->formCollectionFactory->create();
        $collection->addFieldToFilter(FormInterface::STATUS, ['eq' => Form::STATUS_ENABLED]);

        return $collection;
    }
}
