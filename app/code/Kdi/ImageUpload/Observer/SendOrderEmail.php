<?php
declare(strict_types=1);

namespace Kdi\ImageUpload\Observer;

use Kdi\ImageUpload\Helper\Data as DataConfig;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class SendOrderEmail implements ObserverInterface
{
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var DataConfig
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param State $appState
     * @param DataConfig $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        State $appState,
        DataConfig $config,
        LoggerInterface $logger
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->storeManager     = $storeManager;
        $this->appState         = $appState;
        $this->config           = $config;
        $this->logger           = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order || !$order->getId()) {
            return;
        }

        $storeId = (int) $order->getStoreId();

        // -------------------------------------------------------
        // Guard 1: Module must be enabled
        // -------------------------------------------------------
        if (!$this->config->isEnabled($storeId)) {
            $this->logger->info('[Kdi_ImageUpload] Module disabled - skipping submit photos email.');
            return;
        }

        // -------------------------------------------------------
        // Guard 2: Image upload feature must be enabled
        // -------------------------------------------------------
        if (!$this->config->isImageUploadEnabled($storeId)) {
            $this->logger->info('[Kdi_ImageUpload] Image upload disabled - skipping submit photos email.');
            return;
        }

        // -------------------------------------------------------
        // Guard 3: Must be a registered (logged-in) customer
        // -------------------------------------------------------
        $customerId = $order->getCustomerId();
        if (!$customerId) {
            $this->logger->info(
                '[Kdi_ImageUpload] Order #' . $order->getIncrementId() . ' is a guest order - skipping.'
            );
            return;
        }

        // -------------------------------------------------------
        // Collect required data
        // -------------------------------------------------------
        $customerEmail = (string) $order->getCustomerEmail();
        $customerName  = trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname());
        $orderId       = (string) $order->getIncrementId();
        $templateId    = $this->storeConfig->getConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId, $storeScope);
        $sender        = $this->config->getSubmitPhotosEmailSender($storeId);

        if (empty($customerEmail) || empty($templateId)) {
            $this->logger->warning(
                '[Kdi_ImageUpload] Missing email or template ID for order #' . $orderId . ' - skipping.'
            );
            return;
        }

        // -------------------------------------------------------
        // Build upload URL
        // -------------------------------------------------------
        try {
            $baseUrl   = $this->storeManager->getStore($storeId)->getBaseUrl();
            $uploadUrl = rtrim($baseUrl, '/') . '/image_upload?order_id=' . $orderId;
        } catch (\Exception $e) {
            $this->logger->error(
                '[Kdi_ImageUpload] Could not resolve store base URL: ' . $e->getMessage()
            );
            return;
        }

        $templateVars = [
            'customer_name' => $customerName,
            'order_id'      => $orderId,
            'upload_url'    => $uploadUrl,
        ];

        // -------------------------------------------------------
        // Send email
        // Wrapped in emulateAreaCode(frontend) to prevent:
        // "LESS file is empty: adminhtml/.../email-inline.less"
        // -------------------------------------------------------
        try {
            $this->appState->emulateAreaCode(
                Area::AREA_FRONTEND,
                function () use ($customerEmail, $customerName, $templateId, $templateVars, $sender, $storeId) {
                    $transport = $this->transportBuilder
                        ->setTemplateIdentifier($templateId)
                        ->setTemplateOptions([
                            'area'  => Area::AREA_FRONTEND,
                            'store' => $storeId,
                        ])
                        ->setTemplateVars($templateVars)
                        ->setFromByScope($sender, $storeId)
                        ->addTo($customerEmail, $customerName)
                        ->getTransport();

                    $transport->sendMessage();
                }
            );

            $this->logger->info(
                '[Kdi_ImageUpload] Submit photos email sent for order #' . $orderId
                . ' to ' . $customerEmail
            );

        } catch (LocalizedException $e) {
            $this->logger->error(
                '[Kdi_ImageUpload] Mail LocalizedException for order #' . $orderId
                . ': ' . $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->logger->error(
                '[Kdi_ImageUpload] Mail Exception for order #' . $orderId
                . ': ' . $e->getMessage()
            );
        }
    }
}