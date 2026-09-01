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

use Magento\Framework\Controller\ResultFactory;

class Preview extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Security\Model\ResourceModel\AdminSessionInfo\CollectionFactory
     */
    protected $adminSessionInfoCollectionFactory;

    /**
     * @var \Magezon\PopupBuilder\Model\PopupFactory
     */
    protected $popupFactory;

    /**
     * @param \Magento\Framework\App\Action\Context                                    $context
     * @param \Magento\Framework\Controller\Result\ForwardFactory                      $resultForwardFactory
     * @param \Magento\Framework\Registry                                              $coreRegistry
     * @param \Magento\Security\Model\ResourceModel\AdminSessionInfo\CollectionFactory $adminSessionInfoCollectionFactory
     * @param \Magezon\PopupBuilder\Model\PopupFactory                                 $popupFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Security\Model\ResourceModel\AdminSessionInfo\CollectionFactory $adminSessionInfoCollectionFactory,
        \Magezon\PopupBuilder\Model\PopupFactory $popupFactory
    ) {
        parent::__construct($context);
        $this->resultForwardFactory              = $resultForwardFactory;
        $this->coreRegistry                      = $coreRegistry;
        $this->adminSessionInfoCollectionFactory = $adminSessionInfoCollectionFactory;
        $this->popupFactory                       = $popupFactory;
    }

    /**
     * Form view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $popupId     = (int) $this->getRequest()->getParam('popup_id');
        $key        = (string) $this->getRequest()->getParam('key');
        if ($key && $popupId) {
            /*$sessionCollection = $this->adminSessionInfoCollectionFactory->create();
            $sessionCollection->addFieldToFilter('session_id', $key)
            ->addFieldToFilter('status', 1);
            if ($sessionCollection->count()) {*/
                $popup = $this->popupFactory->create();
                $popup->load($popupId);
                if ($popup->getId()) {
                    $this->coreRegistry->register('popupbuilder_popup', $popup);
                    return $resultPage;
                }
            //}
        }
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('noroute');
    }
}
