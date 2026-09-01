<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Model;

use Magento\Framework\Api\DataObjectHelper;
use Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface;
use Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterfaceFactory;

class GeneralQuestions extends \Magento\Framework\Model\AbstractModel
{

    protected $dataObjectHelper;

    protected $generalquestionsDataFactory;

    protected $_eventPrefix = 'nilesh_generalquestions_generalquestions';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param GeneralQuestionsInterfaceFactory $generalquestionsDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions $resource
     * @param \Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        GeneralQuestionsInterfaceFactory $generalquestionsDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions $resource,
        \Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions\Collection $resourceCollection,
        array $data = []
    ) {
        $this->generalquestionsDataFactory = $generalquestionsDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve generalquestions model with generalquestions data
     * @return GeneralQuestionsInterface
     */
    public function getDataModel()
    {
        $generalquestionsData = $this->getData();
        
        $generalquestionsDataObject = $this->generalquestionsDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $generalquestionsDataObject,
            $generalquestionsData,
            GeneralQuestionsInterface::class
        );
        
        return $generalquestionsDataObject;
    }
}

