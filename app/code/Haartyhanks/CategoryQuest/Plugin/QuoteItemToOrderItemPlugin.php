<?php

namespace Haartyhanks\CategoryQuest\Plugin;

use Magento\Customer\Model\Session;
use Amasty\Customform\Model\AnswerFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;


class QuoteItemToOrderItemPlugin
{
    private const LOG_FILE = BP . '/var/log/CateQuest.log';

    private  $session;
    private  $answerFactory;
    private  $productRepository;
    private  $categoryFactory;
    private  $logger;
    private  $remoteAddress;
    private  $scopeConfig;

    const XML_PATH_FOR_GUEST = 'categery/weightloss/guest';
    const XML_PATH_FOR_REAPEAT = 'categery/weightloss/repeat';

    public function __construct(
        Session                    $session,
        AnswerFactory              $answerFactory,
        ProductRepositoryInterface $productRepository,
        CategoryFactory            $categoryFactory,
        RemoteAddress              $remoteAddress,
        ScopeConfigInterface       $scopeConfig
    ) {
        $this->session           = $session;
        $this->answerFactory     = $answerFactory;
        $this->productRepository = $productRepository;
        $this->categoryFactory   = $categoryFactory;
        $this->logger            = $this->initLogger();
        $this->remoteAddress     = $remoteAddress;
        $this->scopeConfig       = $scopeConfig;
    }

    /**
     * Around convert plugin to attach questionnaire unique ID to order item.
     */
    public function aroundConvert(
        ToOrderItem    $subject,
        \Closure       $proceed,
        AbstractItem   $item,
        array          $additional = []
    ): OrderItem {
        /** @var OrderItem $orderItem */
        $orderItem = $proceed($item, $additional);

        try {
            $this->attachQuestionnaireUniqueId($item, $orderItem);
        } catch (\Exception $e) {
            $this->logger->err('[QuoteItemToOrderItemPlugin] Exception: ' . $e->getMessage());
        }

        return $orderItem;
    }

    /**
     * Core logic: resolve and attach questionnaire_unique_id to order item.
     */
    private function attachQuestionnaireUniqueId(AbstractItem $item, OrderItem $orderItem): void
    {
        // If item already has a questionnaire unique ID, carry it forward
        if ($item->getQuestionnaireUniqueId() !== null) {
            $this->logger->info('[QuoteItem] Existing questionnaire_unique_id found, carrying forward.');
            $orderItem->setQuestionnaireUniqueId($item->getQuestionnaireUniqueId());
            return;
        }
        $storeId = $item->getStoreId();
        $this->logger->info("[QuoteItem] Item Store ID: {$storeId}");


        $categoryFormId = $this->resolveCategoryFormId($item);

        if($storeId == 2){
            $categoryFormId = $this->getConfigValue(QuoteItemToOrderItemPlugin::XML_PATH_FOR_GUEST);
        }
        
        if (empty($categoryFormId)) {
            $this->logger->info('[QuoteItem] No category_form_id found. Skipping.');
            return;
        }

        // Get Store ID from quote item
        $storeId = $item->getStoreId();
        $this->logger->info("[QuoteItem] Item Store ID: {$storeId}");

        $this->logger->info("[QuoteItem] Resolved category_form_id: {$categoryFormId}");

        $uniqueId = $this->resolveUniqueIdForLoggedInCustomer($categoryFormId, $storeId);
        $this->logger->info("[QuoteItem] Setting questionnaire_unique_id: {$uniqueId}");
        if (!empty($uniqueId)) {
            $this->logger->info("[QuoteItem] Setting questionnaire_unique_id: {$uniqueId}");
            $orderItem->setQuestionnaireUniqueId($uniqueId);
        } else {
            $this->logger->info('[QuoteItem] No matching questionnaire answer found.');
        }
    }

    /**
     * Resolve category_form_id from the product's categories.
     */
    private function resolveCategoryFormId(AbstractItem $item): ?string
    {
        $productId = $this->getVisibleProductId($item);

        $this->logger->info("[QuoteItem] Resolved product ID: {$productId}");

        $product     = $this->productRepository->getById($productId);
        $categoryIds = $product->getCategoryIds();

        $this->logger->info('[QuoteItem] Category IDs: ' . implode(', ', $categoryIds));

        foreach ($categoryIds as $categoryId) {
            $category   = $this->categoryFactory->create()->load($categoryId);
            $formId     = $category->getData('category_form_id');

            $this->logger->info("[QuoteItem] Category {$categoryId} → category_form_id: " . ($formId ?? 'null'));

            if (!empty($formId)) {
                return (string) $formId;
            }
        }

        return null;
    }

    /**
     * Get the visible product ID from the quote item.
     * For configurable/grouped child items, use parent product ID.
     */
    private function getVisibleProductId(AbstractItem $item): int
    {
        $product = $item->getProduct();

        return $product->getVisibleInSiteVisibilities()
            ? (int) $item->getProductId()
            : (int) ($item->getParentItemId() ?? $item->getProductId());
    }

    /**
     * Resolve unique_id for logged-in customer by matching email + form_id.
     */
    private function resolveUniqueIdForLoggedInCustomer(string $categoryFormId, $storeId = 1 ): ?string
    {
        $customerEmail = $this->session->getCustomer()->getEmail();
        $currentIp = $this->getCurrentIP();
        $this->logger->info("[QuoteItem] Logged-in customer email: {$customerEmail}");
        $this->logger->info("[QuoteItem] Current IP : {$customerEmail}");
        


        if ($customerEmail) {
            $collection = $this->answerFactory->create()
            ->getCollection()
            ->addFieldToFilter('form_id', ['eq' => $categoryFormId])
            ->addFieldToFilter(
                ['admin_response_email', 'ip'],
                [
                    ['eq' => $customerEmail],
                    ['eq' => $currentIp]
                ]
            )
            ->setPageSize(1)
            ->setOrder('answer_id', 'DESC');
        } else {
            $collection = $this->answerFactory->create()
            ->getCollection()
            ->addFieldToFilter('ip', ['eq' => $currentIp])
            ->addFieldToFilter('form_id', ['eq' => $categoryFormId])
            ->setPageSize(1)
            ->setOrder('answer_id', 'DESC');

        }

        if ($storeId == 2 ) {
            if ($customerEmail) {
                $collection = $this->answerFactory->create()
                ->getCollection()
                ->addFieldToFilter(
                    'form_id',
                    [
                        ['eq' => $this->getConfigValue(QuoteItemToOrderItemPlugin::XML_PATH_FOR_GUEST)],
                        ['eq' => $this->getConfigValue(QuoteItemToOrderItemPlugin::XML_PATH_FOR_REAPEAT)]
                    ]
                )
                ->addFieldToFilter(
                    ['admin_response_email', 'ip'],
                    [
                        ['eq' => $customerEmail],
                        ['eq' => $currentIp]
                    ]
                )
                ->setPageSize(1)
                ->setOrder('answer_id', 'DESC');
            } else {
                $collection = $this->answerFactory->create()
                ->getCollection()
                ->addFieldToFilter('ip', ['eq' => $currentIp])
                ->addFieldToFilter('form_id', ['eq' => $categoryFormId])
                ->setPageSize(1)
                ->setOrder('answer_id', 'DESC');
    
            }
        }
        $this->logger->info('[QuoteItem] Store  (logged-in): ' . $storeId);
        $this->logger->info('[QuoteItem] SQL (logged-in): ' . $collection->getSelect()->__toString());
        $this->logger->info('[QuoteItem] Collection size: ' . $collection->getSize());

        if ($collection->getSize() === 0) {
            return null;
        }

        $answer = $collection;

        $this->logger->info('[QuoteItem] Matched answer data: ' . print_r($answer->getData(), true));

        return $answer->getData()[0]['questionnaire_unique_id'] ?: null;
    }

    /**
     * Resolve unique_id for guest customer by matching ip + form_id.
     */
    private function resolveUniqueIdForGuest(AbstractItem $item, string $categoryFormId): ?string
    {
        $CurrentIP = (int) $this->getCurrentIP();

        $this->logger->info("[QuoteItem] Guest quote IP : {$CurrentIP}");

        $collection = $this->answerFactory->create()
            ->getCollection()
            ->addFieldToFilter('ip', ['eq' => $CurrentIP])
            ->addFieldToFilter('form_id', ['eq' => $categoryFormId])
            ->setPageSize(1)
            ->setOrder('answer_id', 'DESC');

        $this->logger->info('[QuoteItem] SQL (guest): ' . $collection->getSelect()->__toString());
        $this->logger->info('[QuoteItem] Collection size: ' . $collection->getSize());

        if ($collection->getSize() === 0) {
            return null;
        }

        $answer = $collection->getFirstItem();

        $this->logger->info('[QuoteItem] Matched answer data: ' . print_r($answer->getData(), true));

        return $answer->getData('questionnaire_unique_id') ?: null;
    }

    /**
     * Initialize Zend logger with a stream writer.
     */
    private function initLogger(): \Zend_Log
    {
        $writer = new \Zend_Log_Writer_Stream(self::LOG_FILE);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        return $logger;
    }

    public function getCurrentIP() 
    {
        $visitorIp = $this->remoteAddress->getRemoteAddress();
        return $visitorIp;
    }

    public function getConfigValue($path) 
    { 
        return $this->scopeConfig->getValue( 
        $path, 
        ScopeInterface::SCOPE_STORE, 
     ); 
    } 
}