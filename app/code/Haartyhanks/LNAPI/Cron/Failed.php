<?php
namespace Haartyhanks\LNAPI\Cron;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Failed
{
    protected $transportBuilder;
    protected $scopeConfig;
    protected $logger;
    private $storeManager;

    public function __construct(
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        try {
            
            $toEmail = 'nitin@haartyhanks.com';
            $emailData = [
                'customer_name' => 'First Last',
                'application_status' => 'Failed',
                'brand_name' => 'X Pharma Ltd',
            ];
            $templateId = 40;

           // $this->sendEmail($toEmail, $emailData, $templateId);
            $this->logger->info('Cron email sent successfully by Haartyhanks_LNAPI!');
        } catch (\Exception $e) {
            $this->logger->error('Error sending email in Haartyhanks_LNAPI cron: ' . $e->getMessage());
        }
    }


   private function sendEmail(string $toEmail, array $data, int $templateId): void
   {
       try {
           $transport = $this->transportBuilder
               ->setTemplateIdentifier($templateId)
               ->setTemplateOptions([
                   'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                   'store' => $this->storeManager->getStore()->getId(),
                ])
                ->setTemplateVars($data)
                ->setFromByScope('general')
                ->addTo($toEmail)
               ->getTransport();

           $transport->sendMessage();
       } catch (MailException|LocalizedException $e) {
           $this->logger->error('Email sending failed: ' . $e->getMessage());
        }
    }
}
