<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Nilesh\GpManagement\Controller\Index;

use Nilesh\GpManagement\Model\GpManagementFactory as GpManagement;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    protected $jsonHelper;
    protected $gpapi;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Psr\Log\LoggerInterface $logger,
        GpManagement $gpapi
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->gpapi = $gpapi;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // Post value
        $query = $this->getRequest()->getPostValue();
        $q = isset($query['q']) ? $query['q'] : '';
        if (!empty($q)) {

            // Setting up factory
            $model = $this->gpapi->create();
            $collection = $model->getCollection();
            $collection->addFieldToFilter(array('practice_code', 'name_of_practice'), array(array('like' => '%' . $q . '%'), array('like' => '%' . $q . '%')));
            // $collection->setPageSize(20);
            // \print_r($model); exit();
            try {
                return $this->jsonResponse($collection->getData());
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                return $this->jsonResponse($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->critical($e);
                return $this->jsonResponse($e->getMessage());
            }
        } else {
            return $this->jsonResponse([]);
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
