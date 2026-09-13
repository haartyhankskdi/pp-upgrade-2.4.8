<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\Helpdesk\Controller\Adminhtml\Report;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

class View extends Action
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var \Mirasvit\Report\Api\Repository\ReportRepositoryInterface
     */
    private $reportRepository;
    /**
     * @var Registry
     */
    private $registry;

    /**
     * View constructor.
     * @param \Mirasvit\Report\Api\Repository\ReportRepositoryInterface $reportRepository
     * @param Registry $registry
     * @param Context $context
     */
    public function __construct(
        \Mirasvit\Report\Api\Repository\ReportRepositoryInterface $reportRepository,
        Registry $registry,
        Context $context
    ) {
        $this->reportRepository = $reportRepository;
        $this->registry = $registry;
        $this->context = $context;
        parent::__construct($context);
    }
    /**
     * {@inheritdoc}
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Mirasvit_Helpdesk::helpdesk_report');
        $resultPage->getConfig()->getTitle()->prepend(__('Helpdesk'));
        $resultPage->getConfig()->getTitle()->prepend(__('Reports'));
        return $resultPage;
    }
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->registry->register('current_report', $this->reportRepository->get('helpdesk_overview'));
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $this->initPage($resultPage);
        return $resultPage;
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->context->getAuthorization()->isAllowed('Mirasvit_Helpdesk::helpdesk_report');
    }
}