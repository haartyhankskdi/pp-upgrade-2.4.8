<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Model\BannerFactory;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner as ResourceModel;
use Psr\Log\LoggerInterface;

/**
 * Class Banner
 * @package Mageplaza\PromoBanner\Controller\Adminhtml
 */
abstract class Banner extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageplaza_PromoBanner::banner';

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var BannerFactory
     */
    protected $bannerFactory;

    /**
     * @var ResourceModel
     */
    protected $resourceModel;

    /**
     * @var Date
     */
    protected $_dateFilter;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Banner constructor.
     *
     * @param Action\Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param BannerFactory $bannerFactory
     * @param ResourceModel $resourceModel
     * @param Date $dateFilter
     * @param Data $helperData
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        ForwardFactory $resultForwardFactory,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        BannerFactory $bannerFactory,
        ResourceModel $resourceModel,
        Date $dateFilter,
        Data $helperData,
        LoggerInterface $logger
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultPageFactory    = $resultPageFactory;
        $this->_coreRegistry        = $coreRegistry;
        $this->bannerFactory        = $bannerFactory;
        $this->resourceModel        = $resourceModel;
        $this->_dateFilter          = $dateFilter;
        $this->helperData           = $helperData;
        $this->logger               = $logger;
        parent::__construct($context);
    }

    /**
     * @return Page
     */
    protected function initPage()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Mageplaza_PromoBanner::banner')
            ->addBreadcrumb(__('Promo Banner'), __('Promo Banner'))
            ->addBreadcrumb(__('Banners'), __('Banners'));

        return $resultPage;
    }
}
