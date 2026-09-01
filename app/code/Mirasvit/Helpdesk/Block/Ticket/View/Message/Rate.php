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



namespace Mirasvit\Helpdesk\Block\Ticket\View\Message;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\Helpdesk\Model\Message;
use Mirasvit\Helpdesk\Repository\SatisfactionRepository;

class Rate extends Template
{
    /**
     * @var Message
     */
    private $message;

    private $context;

    private $satisfactionRepository;

    public function __construct(
        SatisfactionRepository $satisfactionRepository,
        Context $context,
        array $data = []
    ) {
        $this->satisfactionRepository = $satisfactionRepository;

        $this->context = $context;

        parent::__construct($context, $data);
    }

    /**
     * Escape HTML entities
     *
     * @param string|array $data
     * @param array|null   $allowedTags
     *
     * @return string
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        //html can contain incorrect symbols which produce warrnings to log
        $internalErrors = libxml_use_internal_errors(true);
        $res            = parent::escapeHtml($data, $allowedTags);
        libxml_use_internal_errors($internalErrors);

        return $res;
    }

    /**
     * @return array
     */
    public function getRateImages()
    {
        return [
            1 => $this->getViewFileUrl('Mirasvit_Helpdesk::images/smile/1.png', ['_area' => 'frontend']),
            2 => $this->getViewFileUrl('Mirasvit_Helpdesk::images/smile/2.png', ['_area' => 'frontend']),
            3 => $this->getViewFileUrl('Mirasvit_Helpdesk::images/smile/3.png', ['_area' => 'frontend']),
        ];
    }

    /**
     * @param int $rate possible values: 1,2,3
     * @return string
     */
    public function getSatisfactionUrl($rate)
    {
        return $this->getUrl(
            'helpdesk/satisfaction/rate',
            ['rate' => $rate, 'uid' => $this->message->getUid(), '_nosid' => true]
        );
    }

    public function getSatisfactionComment()
    {
        $satisfaction = $this->satisfactionRepository->getByMessage($this->message);

        $comment = '';
        if ($satisfaction) {
            $comment = $satisfaction->getComment();
        }

        return $comment;
    }

    public function getRateImageUrl()
    {
        $satisfaction = $this->satisfactionRepository->getByMessage($this->message);

        $url = '';
        if ($satisfaction) {
            $urls = $this->getRateImages();
            $url  = isset($urls[$satisfaction->getRate()]) ? $urls[$satisfaction->getRate()] : '';
        }

        return $url;
    }

    public function setItem(Message $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }
}
