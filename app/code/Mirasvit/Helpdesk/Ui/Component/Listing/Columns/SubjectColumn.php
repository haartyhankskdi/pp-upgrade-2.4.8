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



namespace Mirasvit\Helpdesk\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Model\Message;
use Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use Mirasvit\Helpdesk\Helper\StringUtil;
use Magento\Framework\Escaper;

class SubjectColumn extends Column
{
    /**
     * @var MessageCollectionFactory
     */
    private $messageCollectionFactory;

    /**
     * @var StringUtil
     */
    private $stringUtil;

    private $escaper;

    /**
     * SubjectColumn constructor.
     * @param MessageCollectionFactory $messageCollectionFactory
     * @param StringUtil $stringUtil
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        Escaper $escaper,
        MessageCollectionFactory $messageCollectionFactory,
        StringUtil $stringUtil,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->stringUtil = $stringUtil;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @var int
     */
    private $len = 50;

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = $this->prepareItem($this->getData('name'), $item);

                $this->addQuickViewInfo($item);
            }
        }

        return $dataSource;
    }

    /**
     * Format data.
     *
     * @param string $fieldName
     * @param array $item
     * @return string
     */
    protected function prepareItem($fieldName, array $item)
    {
        $subject = $item[$fieldName];
        if (strlen($subject) > $this->len) {
            $subject = mb_substr($subject, 0, $this->len) . '...';
        }

        return $subject;
    }

    /**
     * @param array $item
     * @return array
     */
    protected function addQuickViewInfo(array &$item)
    {
        $collection   = $this->messageCollectionFactory->create()->addFieldToFilter(TicketInterface::KEY_ID, $item[TicketInterface::KEY_ID]);
        $firstMessage = $collection->getFirstItem();
        $lastMessage  = $collection->getLastItem();

        $firstMessage = $this->prepareMessage($firstMessage);
        $lastMessage  = $this->prepareMessage($lastMessage);

        if ($lastMessage && $lastMessage == $firstMessage) {
            $lastMessage = null;
        }

        if (!isset($firstMessage["message_id"])) {
            $firstMessage = null;
        }

        $item['firstMessage'] = $firstMessage;
        $item['lastMessage']  = $lastMessage;

        return $item;
    }

    /**
     * @param Message $message
     * @return array
     */
    private function prepareMessage($message)
    {
        $data = $message->getData();

        if (isset($data['body'])) {
            $cleanBody = strip_tags($data['body']);
            $data['body'] = nl2br($this->escaper->escapeHtml($cleanBody));

            if (strlen($data['body']) > 400) {
                $data['body'] = mb_substr($data['body'], 0, 400) . '...';
            }
        }

        if (isset($data['created_at'])) {
            $data['created_at'] = $this->stringUtil->nicetime(strtotime($data['created_at']));
        }

        return $data;
    }
}
