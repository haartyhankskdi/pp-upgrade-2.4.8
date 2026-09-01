<?php

declare(strict_types=1);

namespace Kdi\AdLanding\Controller\Category;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

class Listing extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // Redirect to homepage if current store is the default store
        $store = $this->storeManager->getStore();
        if ($store->getId() == 1) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('');
        }


        return $this->resultPageFactory->create();
    }
}
