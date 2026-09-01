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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\Helpdesk\Controller\Adminhtml\Ticket;

use Mirasvit\Helpdesk\Api\Repository\TicketRepositoryInterface;
use Mirasvit\Helpdesk\Model\Config as Config;
use Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\ResultFactory;

class MassStatus extends \Magento\Backend\App\Action
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    protected $redirectUrl = '*/*/index';

    /**
     * @var TicketRepositoryInterface
     */
    protected $ticketRepository;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param TicketRepositoryInterface $ticketRepository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        TicketRepositoryInterface $ticketRepository
    ) {
        parent::__construct($context);

        $this->context           = $context;
        $this->filter            = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->ticketRepository  = $ticketRepository;
    }

    /**
     * @return $this|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        try {
            /** @var \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Collection $collection */
            $collection = $this->filter->getCollection($this->collectionFactory->create());

            return $this->massAction($collection);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

            return $resultRedirect->setPath($this->redirectUrl);
        }
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Collection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(\Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Collection $collection)
    {
        $ticketsUpdated = 0;
        foreach ($collection->getAllIds() as $ticketId) {
            /** @var \Mirasvit\Helpdesk\Model\Ticket $ticket */
            $ticket = $this->ticketRepository->get($ticketId);
            $ticket->setStatusId((int)$this->getRequest()->getParam('status'));
            $this->ticketRepository->save($ticket);
            $ticketsUpdated++;
        }

        if ($ticketsUpdated) {
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been updated.', $ticketsUpdated));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($this->redirectUrl);

        return $resultRedirect;
    }
}
