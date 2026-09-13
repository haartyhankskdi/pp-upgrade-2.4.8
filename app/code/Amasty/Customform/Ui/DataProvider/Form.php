<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Ui\DataProvider;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Model\Form as FormModel;
use Amasty\Customform\Model\FormRegistry;
use Amasty\Customform\Model\ResourceModel\Form\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface as UiDataModifiersPool;

class Form extends AbstractDataProvider
{
    /**
     * @var ?array
     */
    private $loadedData;

    /**
     * @var UiDataModifiersPool
     */
    private $uiDataModifiersPool;

    /**
     * @var FormRegistry
     */
    private $formRegistry;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        FormRegistry $formRegistry,
        CollectionFactory $collectionFactory,
        UiDataModifiersPool $uiDataModifiersPool,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->uiDataModifiersPool = $uiDataModifiersPool;
        $this->formRegistry = $formRegistry;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData === null) {
            $persistedData = $this->formRegistry->registry(FormRegistry::PERSISTED_DATA);

            if (!empty($persistedData)) {
                $this->loadedData[$persistedData[FormInterface::FORM_ID]] = $persistedData->getData();
            } else {
                $form = $this->formRegistry->getCurrentForm();

                if ($form !== null) {
                    $this->loadedData = $form->getData();

                    foreach ($this->uiDataModifiersPool->getModifiersInstances() as $modifier) {
                        $this->loadedData = $modifier->modifyData($this->loadedData);
                    }

                    $this->loadedData[$form->getFormId()] = $this->loadedData;
                } else {
                    $this->loadedData = [];
                }
            }
        }

        return $this->loadedData;
    }

    public function getMeta(): array
    {
        $meta = parent::getMeta();

        foreach ($this->uiDataModifiersPool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }
}
