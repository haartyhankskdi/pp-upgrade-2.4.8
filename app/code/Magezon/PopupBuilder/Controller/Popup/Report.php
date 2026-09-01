<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Controller\Popup;

use Magento\Framework\Exception\LocalizedException;

class Report extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param \Magento\Framework\App\Action\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection  $resource
     * @param \Magento\Customer\Model\Session            $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->storeManager    = $storeManager;
        $this->resource        = $resource;
        $this->customerSession = $customerSession;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $result['status'] = false;
        $post = $this->getRequest()->getPostValue();
        try {
            if (isset($post['type']) && $post['type'] && isset($post['id']) && $post['id']) {
                $this->addReport();
                $result['status'] = true;
            }
        } catch (LocalizedException $e) {
            $this->resource->getConnection()->rollBack();
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->resource->getConnection()->rollBack();
            $this->logger->critical($e);
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
        return;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addReport()
    {
        $post    = $this->getRequest()->getPostValue();
        $table   = $this->resource->getTableName('mgz_popupbuilder_popup_report');
        $storeId = $this->storeManager->getStore()->getId();
        $customerId = $this->customerSession->getId();
        $connection = $this->resource->getConnection();
        $data = [
            'type'        => $post['type'],
            'customer_id' => $customerId,
            'popup_id'    => (int)$post['id'],
            'store_id'    => (int)$storeId
        ];
        $insert = $connection->insert($table, $data);
        return $insert;
    }
}
