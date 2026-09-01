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



namespace Mirasvit\Helpdesk\Controller\Adminhtml;

abstract class Draft extends \Magento\Backend\App\Action
{
    /**
     * @var \Mirasvit\Helpdesk\Api\Repository\TicketRepositoryInterface
     */
    protected $ticketRepository;
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;
    /**
     * @var \Mirasvit\Helpdesk\Helper\Draft
     */
    protected $helpdeskDraft;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;
    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;

    /**
     * Draft constructor.
     * @param \Mirasvit\Helpdesk\Api\Repository\TicketRepositoryInterface $ticketRepository
     * @param \Mirasvit\Helpdesk\Helper\Draft $helpdeskDraft
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Mirasvit\Helpdesk\Api\Repository\TicketRepositoryInterface $ticketRepository,
        \Mirasvit\Helpdesk\Helper\Draft $helpdeskDraft,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->ticketRepository = $ticketRepository;
        $this->helpdeskDraft    = $helpdeskDraft;
        $this->formKey          = $formKey;
        $this->jsonHelper       = $jsonHelper;
        $this->context          = $context;
        $this->resultFactory    = $context->getResultFactory();

        parent::__construct($context);
    }

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->context->getAuthorization()->isAllowed('Mirasvit_Helpdesk::helpdesk_ticket');
    }
}
