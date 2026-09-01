<?php

namespace Kdi\NoIndexNofollow\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Cms\Model\PageFactory as CmsPageFactory;
use Magento\Cms\Helper\Page as CmsPageHelper;

class UpdateRobotsMeta implements ObserverInterface
{
    protected $pageConfig;
    protected $cmsFactory;
      protected $cmsPageHelper;

    public function __construct(PageConfig $pageConfig, CmsPageFactory $cmsFactory)
    {
        $this->pageConfig = $pageConfig;
        $this->cmsFactory = $cmsFactory;
    }

    public function execute(Observer $observer)
    {
        $fullActionName = $observer->getEvent()->getFullActionName();
        $layout = $observer->getEvent()->getLayout();

        // Initialize the default robots meta value
        $defaultRobots = 'INDEX, FOLLOW';


             if ($fullActionName == 'cms_index_index' || $fullActionName == 'cms_page_view') {
            // Get the page ID for CMS pages
            $pageId = $layout->getBlock('cms_page')->getPage()->getId();

            if ($pageId) {
                $cmsPage = $this->cmsFactory->create()->load($pageId);
                $defaultRobots = $cmsPage->getData('default_robots') ?: $defaultRobots;
            }

       

        // Set the robots meta tag
        $this->pageConfig->setMetadata('robots', $defaultRobots);
    }

        // return $this;
    }
}
