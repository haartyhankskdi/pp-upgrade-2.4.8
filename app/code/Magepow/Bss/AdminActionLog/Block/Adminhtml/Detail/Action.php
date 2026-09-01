<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AdminActionLog
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AdminActionLog\Block\Adminhtml\Detail;

use Magento\Backend\Block\Template;
use Magento\Framework\App\Request\Http;
use Magento\User\Model\UserFactory;

class Action extends Template
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @var \Bss\AdminActionLog\Model\ActionGridFactory
     */
    protected $actionFactory;

    /**
     * @var \Bss\AdminActionLog\Model\ResourceModel\ActionDetail\CollectionFactory $actionDetailCollectionFactory
     */
    protected $actionDetailCollectionFactory;

    /**
     * @var
     */
    protected $sactionDetailCollectionFactory;

    /**
     * @var
     */
    protected $_localeDate;

    /**
     * @var \Bss\AdminActionLog\Convert\FineDiffFactory
     */
    protected $convert;

    /**
     * Action constructor.
     * @param Template\Context $context
     * @param Http $request
     * @param UserFactory $userFactory
     * @param \Bss\AdminActionLog\Model\ActionGridFactory $actionFactory
     * @param \Bss\AdminActionLog\Model\ResourceModel\ActionDetail\CollectionFactory $actionDetailCollectionFactory
     * @param \Bss\AdminActionLog\Convert\FineDiffFactory $convert
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Http $request,
        UserFactory $userFactory,
        \Bss\AdminActionLog\Model\ActionGridFactory $actionFactory,
        \Bss\AdminActionLog\Model\ResourceModel\ActionDetail\CollectionFactory $actionDetailCollectionFactory,
        \Bss\AdminActionLog\Convert\FineDiffFactory $convert,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $request;
        $this->userFactory = $userFactory;
        $this->actionFactory = $actionFactory;
        $this->actionDetailCollectionFactory = $actionDetailCollectionFactory;
        $this->convert = $convert;
    }

    /**
     * Get Log
     *
     * @return \Bss\AdminActionLog\Model\ActionGrid
     */
    public function getLog()
    {
        $params = $this->request->getParams();
        $actionlog = $this->actionFactory->create()->load($params['id']);
        return $actionlog;
    }

    /**
     * Get CreatedAt
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->_localeDate->formatDateTime(
            $this->getLog()->getCreatedAt(),
            \IntlDateFormatter::MEDIUM
        );
    }

    /**
     * Get Details
     *
     * @return \Bss\AdminActionLog\Model\ResourceModel\ActionDetail\Collection
     */
    public function getDetails()
    {
        $log = $this->getLog();
        $collecttion = $this->actionDetailCollectionFactory->create();
        $collecttion->addFieldToFilter('log_id', $log->getId());
        return $collecttion;
    }

    /**
     * get User wish UserId
     *
     * @param $userId
     * @return \Magento\User\Model\User
     */
    public function getUser($userId)
    {
        return $this->userFactory->create()->load($userId);
    }

    /**
     * Get Url Revert
     *
     * @return string
     */
    public function getUrlRevert()
    {
        $log =  $this->getLog();
        return $this->getUrl('bssadmin/config/revert', ['id' => $log->getId()]);
    }


    /**
     * Get Decorated Diff
     *
     * @param $old
     * @param $new
     * @return array
     */
    public function getDecoratedDiff($old, $new)
    {
        $fromText = substr($old, 0, 1024*100);
        $toText = substr($new, 0, 1024*100);
        $fromText = str_replace(',', ', ', $fromText);
        $toText = str_replace(',', ', ', $toText);
        $fromText = htmlentities($fromText);
        $toText = htmlentities($toText);

        $diffOpcodes = $this->convert->create()->getDiffOpcodes($fromText, $toText);

        $textNew = $this->convert->create()->_renderDiffToHTML($toText, $diffOpcodes);
        $textOld = $this->convert->create()->renderDiffToHTML($fromText, $diffOpcodes);

        return ["old"=>$textOld, "new"=>$textNew];
    }
}
