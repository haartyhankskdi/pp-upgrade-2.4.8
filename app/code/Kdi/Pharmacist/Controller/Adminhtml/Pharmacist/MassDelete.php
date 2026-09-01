<?php

namespace Kdi\Pharmacist\Controller\Adminhtml\Pharmacist;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Kdi\Pharmacist\Model\ResourceModel\Pharmacist\CollectionFactory;

class MassDelete extends Action
{
    protected $collectionFactory;

    public function __construct(
        Action\Context $context,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $ids = $this->getRequest()->getParam('selected'); // Get selected IDs
        if (!is_array($ids) || empty($ids)) {
            $this->messageManager->addError(__('Please select item(s) to delete.'));
        } else {
            try {
                $collection = $this->collectionFactory->create()
                    ->addFieldToFilter('entity_id', ['in' => $ids]);
                foreach ($collection as $item) {
                    $item->delete();
                }
                $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', count($ids)));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}
