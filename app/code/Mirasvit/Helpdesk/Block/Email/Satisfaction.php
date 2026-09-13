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



namespace Mirasvit\Helpdesk\Block\Email;

use Mirasvit\Helpdesk\Model\TicketFactory;
use Mirasvit\Helpdesk\Model\Config;
use Magento\Framework\View\Element\Template\Context;

/**
 * @method \Mirasvit\Helpdesk\Model\Ticket getTicket()
 * @method $this setTicket(\Mirasvit\Helpdesk\Model\Ticket $param)
 */
class Satisfaction extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Mirasvit\Helpdesk\Model\TicketFactory
     */
    private $ticketFactory;
    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    private $config;

    /**
     * Satisfaction constructor.
     * @param \Mirasvit\Helpdesk\Model\TicketFactory           $ticketFactory
     * @param \Mirasvit\Helpdesk\Model\Config                  $config
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        TicketFactory $ticketFactory,
        Config $config,
        Context $context,
        array $data = []
    ) {
        $this->ticketFactory = $ticketFactory;
        $this->config        = $config;

        parent::__construct($context, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setData('area', 'frontend');
    }

    /**
     * @param string $rate
     * @return string
     */
    public function getRateUrl($rate)
    {
        $message = $this->getTicket()->getLastMessage();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $urlBuilder = $this->_appState->emulateAreaCode(
            'frontend',
            [$objectManager, 'create'],
            ['\Magento\Framework\Url']
        );
        if ($this->getTicket()->getStore()) {
            $urlBuilder->setScope($this->getTicket()->getStore()->getId());
        }
        $url = $urlBuilder->getUrl(
            'helpdesk/satisfaction/rate',
            ['rate' => $rate, 'uid' => $message->getUid(), '_nosid' => true]
        );

        return $url;
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Ticket | null
     */
    public function getTicket() {

        if ($this->getData('ticket')) {
            return $this->getData('ticket');
        }

        $ticketId = (int) $this->getRequest()->getParam('id');

        if (!$ticketId) {
            $ticketId = $this->getData('ticket_id');
        }

        if ($ticketId) {
            return $this->ticketFactory->create()->load($ticketId);
        }

        return null;
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->config->getSatisfactionIsActive()) {
            return '';
        }

        return parent::_toHtml();
    }
}
