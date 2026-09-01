<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Popup\Model;

use Kdi\Popup\Api\Data\EmailInterface;
use Kdi\Popup\Api\Data\EmailInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

class Email extends \Magento\Framework\Model\AbstractModel
{

    protected $emailDataFactory;

    protected $_eventPrefix = 'kdi_popup_email';
    protected $dataObjectHelper;


    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param EmailInterfaceFactory $emailDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Kdi\Popup\Model\ResourceModel\Email $resource
     * @param \Kdi\Popup\Model\ResourceModel\Email\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        EmailInterfaceFactory $emailDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Kdi\Popup\Model\ResourceModel\Email $resource,
        \Kdi\Popup\Model\ResourceModel\Email\Collection $resourceCollection,
        array $data = []
    ) {
        $this->emailDataFactory = $emailDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve email model with email data
     * @return EmailInterface
     */
    public function getDataModel()
    {
        $emailData = $this->getData();
        
        $emailDataObject = $this->emailDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $emailDataObject,
            $emailData,
            EmailInterface::class
        );
        
        return $emailDataObject;
    }
}

