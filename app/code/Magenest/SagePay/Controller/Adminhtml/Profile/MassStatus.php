<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Magenest\SagePay\Model\ProfileFactory;
use Magento\Framework\Exception\LocalizedException;

class MassStatus extends Action
{
    protected $_filter;

    protected $_profileFactory;

    protected $_logger;

    public function __construct(
        Filter $filter,
        ProfileFactory $profileFactory,
        LoggerInterface $loggerInterface,
        Action\Context $context
    ) {
        $this->_logger = $loggerInterface;
        $this->_filter = $filter;
        $this->_profileFactory = $profileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $status = (int)$this->getRequest()->getParam('status');
        $collection = $this->_filter->getCollection($this->_profileFactory->create()->getCollection());
        $total = 0;

        try {
            foreach ($collection as $item) {
                $item->setData('status', $status)->save();
                $total++;
            }
            $this->messageManager->addSuccess(__('A total of %1 record(s) have been updated.', $total));
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}
