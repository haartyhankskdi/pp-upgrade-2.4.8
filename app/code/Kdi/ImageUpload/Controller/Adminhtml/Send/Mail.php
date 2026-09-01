<?php

/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Kdi\ImageUpload\Controller\Adminhtml\Send;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Area;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Kdi\ImageUpload\Helper\Data;
use Psr\Log\LoggerInterface;

class Mail extends Action
{
    /**
     * ACL resource
     */
    // const ADMIN_RESOURCE = 'Kdi_ImageUpload::send_mail';

    /**
     * Store config paths
     */
    const XML_PATH_EMAIL_TEMPLATE = 'image_upload/image_upload_settings/submit_photos_email_template';
    const XML_PATH_FOR_IMAGE_UPLOAD_STATUS = 'image_upload/image_upload_settings/image_upload_status';
    const XML_PATH_SENDER_EMAIL   = 'trans_email/ident_general/email';
    const XML_PATH_SENDER_NAME    = 'trans_email/ident_general/name';
    const XML_PATH_CALENDLY = 'trans_email/ident_general/appointment_book_url';
    const XML_PATH_FOR_BOOK_APPOINTMENT_STATUS = 'image_upload/appointment_booking/appointment_book_status';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Data
     */
    protected $storeConfig;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @param Context                  $context
     * @param PageFactory              $resultPageFactory
     * @param TransportBuilder         $transportBuilder
     * @param StoreManagerInterface    $storeManager
     * @param StateInterface           $state
     * @param OrderRepositoryInterface $orderRepository
     * @param Data                     $data
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface $state,
        OrderRepositoryInterface $orderRepository,
        Data $data,
        LoggerInterface $loggerInterface
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->transportBuilder  = $transportBuilder;
        $this->storeManager      = $storeManager;
        $this->inlineTranslation = $state;
        $this->orderRepository   = $orderRepository;
        $this->storeConfig       = $data;
        $this->_logger           = $loggerInterface;
    }

    /**
     * Send verification email action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $orderId = $this->getRequest()->getParam('order_id');
        $storeId    = (int) $this->storeManager->getStore()->getId();
        $storeScope = ScopeInterface::SCOPE_STORE;

        // -- 0. Check Image Upload Settings Admin Configuration 

        $status  = $this->storeConfig->getConfig(self::XML_PATH_FOR_BOOK_APPOINTMENT_STATUS, $storeId, $storeScope);
        if ($status == 0) {
            $this->messageManager->addErrorMessage(__(' Please Enable Admin Configuration for email sending'));
            $resultRedirect->setPath(
                'sales/order/view',
                ['order_id' => $orderId]
            );
            return $resultRedirect;
        }

        // ── 1. Read & validate order_id from request ─────────────────────────   

        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Invalid order. Please try again.'));
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        // ── 2. Load order & pull customer details ─────────────────────────────
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Order #%1 not found.', $orderId));
            $resultRedirect->setPath(
                'sales/order/view',
                ['order_id' => $orderId]
            );
            return $resultRedirect;
        }

        $customerName  = trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname());
        $customerEmail = trim((string) $order->getCustomerEmail());
        $incrementId   = $order->getIncrementId();





        if (!$customerEmail) {
            $this->messageManager->addErrorMessage(__('Customer email not found for order #%1.', $incrementId));
            $resultRedirect->setPath(
                'sales/order/view',
                ['order_id' => $orderId]
            );
            return $resultRedirect;
        }

        // ── 3. Load store config — sender + template ──────────────────────────

        $fromEmail  = $this->storeConfig->getConfig(self::XML_PATH_SENDER_EMAIL, $storeId, $storeScope);
        $fromName   = $this->storeConfig->getConfig(self::XML_PATH_SENDER_NAME,  $storeId, $storeScope);
        $templateId = $this->storeConfig->getConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId, $storeScope);
        $meetingLink = $this->storeConfig->getConfig(self::XML_PATH_CALENDLY, $storeId, $storeScope);




        if (!$templateId) {
            $this->messageManager->addErrorMessage(
                __('Email template is not configured. Please set it under Stores > Configuration > Kdi > Image Upload.')
            );
            $resultRedirect->setPath(
                'sales/order/view',
                ['order_id' => $orderId]
            );
            return $resultRedirect;
        }

        // ── 4. Build & send email ─────────────────────────────────────────────
        try {
            $templateVars = [
                'customer_name'  => $customerName ?: __('Customer')->render(),
                'customer_email' => $customerEmail,
                'order_id'       => $incrementId,
                'store'          => $this->storeManager->getStore(),
                'meeting_link'   => $meetingLink
            ];

            $templateOptions = [
                'area'  => Area::AREA_FRONTEND,
                'store' => $storeId,
            ];

            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom(['email' => $fromEmail, 'name' => $fromName])
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();

            $this->inlineTranslation->resume();

            $this->messageManager->addSuccessMessage(
                __('Booking Appointment email sent successfully to %1 for Order #%2.', $customerEmail, $incrementId)
            );
        } catch (LocalizedException $e) {
            $this->inlineTranslation->resume();
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_logger->error('[Kdi_ImageUpload] Mail LocalizedException: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->messageManager->addErrorMessage(
                __('Something went wrong while sending the email. Please try again.')
            );
            $this->_logger->info('Template ID: ' . $templateId);
            $this->_logger->info('Area: frontend');
            $this->_logger->critical('[Kdi_ImageUpload] Mail Exception: ' . $e->getMessage());
        }

        // ── 5. Redirect back to previous admin page ───────────────────────────
        $resultRedirect->setPath(
            'sales/order/view',
            ['order_id' => $orderId]
        );
        return $resultRedirect;
    }
}
