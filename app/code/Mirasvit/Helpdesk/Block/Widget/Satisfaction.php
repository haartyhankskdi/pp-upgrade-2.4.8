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



namespace Mirasvit\Helpdesk\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Mirasvit\Helpdesk\Model\ResourceModel\Satisfaction\CollectionFactory as SatisfactionCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Satisfaction\Collection as SatisfactionCollection;
use Mirasvit\Helpdesk\Helper\StringUtil;

class Satisfaction extends Template implements BlockInterface
{
    /**
     * @var SatisfactionCollection
     */
    private $satisfactionCollection;

    /**
     * @var string
     */
    protected $_template = 'satisfaction/block.phtml';

    /**
     * @var SatisfactionCollectionFactory
     */
    protected $satisfactionCollectionFactory;

    /**
     * @var StringUtil
     */
    protected $stringUtil;

    /**
     * Satisfaction constructor.
     * @param SatisfactionCollectionFactory $satisfactionCollectionFactory
     * @param StringUtil $stringUtil
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        SatisfactionCollectionFactory $satisfactionCollectionFactory,
        StringUtil $stringUtil,
        Template\Context $context,
        array $data = []
    ) {
        $this->satisfactionCollectionFactory = $satisfactionCollectionFactory;
        $this->stringUtil = $stringUtil;

        parent::__construct($context, $data);
    }

    /**
     * @return SatisfactionCollection
     */
    public function getCollection()
    {
        if (!$this->satisfactionCollection) {
            $this->satisfactionCollection = $this->satisfactionCollectionFactory->create()
                ->setOrder('created_at', 'desc')
                ->setPageSize(100);
        }

        return $this->satisfactionCollection;
    }

    /**
     * @return array
     */
    public function getAggregatedRate()
    {
        $rates = [
            'great' => 0,
            'ok'    => 0,
            'bad'   => 0,
        ];

        $collection = $this->getCollection();
        if (!$collection->count()) {
            return $rates;
        }

        /** @var \Mirasvit\Helpdesk\Model\Satisfaction $item */
        foreach ($collection as $item) {
            switch ($item->getRate()) {
                case 1:
                    $rates['bad']++;
                    break;
                case 2:
                    $rates['ok']++;
                    break;
                case 3:
                    $rates['great']++;
                    break;
            }
        }
        $ratesAmount = $collection->count();
        $rates['bad']   = round($rates['bad'] / $ratesAmount * 100);
        $rates['ok']    = round($rates['ok'] / $ratesAmount * 100);
        $rates['great'] = round($rates['great'] / $ratesAmount * 100);

        return $rates;
    }

    /**
     * @return string
     */
    public function getLastActivityTime()
    {
        $item = $this->satisfactionCollectionFactory->create()
            ->setOrder('created_at', 'desc')
            ->setPageSize(1)
            ->getFirstItem();

        if ($item->getCreatedAt()) {
            $time = $this->stringUtil->nicetime(strtotime($item->getCreatedAt()));
        } else {
            $time = __('--/--/--');
        }

        return $time;
    }

    /**
     * @param string $comment
     *
     * @return array
     */
    public function parseComment($comment)
    {
        $result = [
            'mark'    => false,
            'note' => '',
        ];

        preg_match('/Mark: ([0-9]*)/', $comment, $matches);

        if (isset($matches[1])) {
            $result['mark'] = intval($matches[1]);
        }

        preg_match('/Comment: (.*)/', $comment, $matches);

        if (isset($matches[1])) {
            $message = strip_tags(trim($matches[1]));

            if ($message) {
                $result['note'] = $message;
            }
        }

        return $result;
    }
}
