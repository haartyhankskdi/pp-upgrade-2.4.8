<?php

declare(strict_types=1);

namespace Kdi\AdLanding\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->storeManager = $storeManager;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
    }

    public function execute()
    {
        $store = $this->storeManager->getStore();
        if ($store->getId() == 1) {
            echo ' default store';
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('');
        }


        return $this->resultPageFactory->create();
    }
}
