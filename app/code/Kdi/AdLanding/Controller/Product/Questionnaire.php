<?php

declare(strict_types=1);

namespace Kdi\AdLanding\Controller\Product;

use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

class Questionnaire implements HttpGetActionInterface
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

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager,
        RedirectFactory $resultRedirectFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->storeManager = $storeManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $store = $this->storeManager->getStore();
        if ($store->getId() == 1) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('');
        }

        return $this->resultPageFactory->create();
    }
}
