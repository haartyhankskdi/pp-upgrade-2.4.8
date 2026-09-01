<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action;
use Magenest\SagePay\Model\ProfileFactory;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{
    protected $_filter;
    protected $profileFactory;
    protected $_logger;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        ProfileFactory $profileFactory,
        LoggerInterface $loggerInterface
    ) {
        $this->_filter = $filter;
        $this->_logger = $loggerInterface;
        $this->profileFactory = $profileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->_filter->getCollection($this->profileFactory->create()->getCollection());
        $profileDeleted = 0;
        foreach ($collection->getItems() as $profile) {
            $profile->delete();
            $profileDeleted++;
        }
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', $profileDeleted)
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}
