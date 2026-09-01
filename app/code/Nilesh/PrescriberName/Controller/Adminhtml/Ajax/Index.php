<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberName\Controller\Adminhtml\Ajax;

class Index extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;
    protected $jsonHelper;
    protected $_order;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order $order
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->_order = $order;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        $prescriber_name = $post['prescriber_name'];
        $order_id = $post['order_id'];
        try {
            if($order_id){
                $order = $this->_order->load($order_id);
                $order->setPrescriberName($prescriber_name);
                $order->save();
            }
            $response = array(
                "status" => true,
                "message" => __("Prescriber Name set successfully")
            );
            return $this->jsonResponse($response);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = array(
                "status" => false,
                "message" => $e->getMessage()
            );
            return $this->jsonResponse($response);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $response = array(
                "status" => false,
                "message" => $e->getMessage()
            );
            return $this->jsonResponse($response);
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

