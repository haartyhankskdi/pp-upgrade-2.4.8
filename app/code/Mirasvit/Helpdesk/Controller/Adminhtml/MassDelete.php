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

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @var string
     */
    private $permission;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;
    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;

    /**
     * @var mixed
     */
    protected $collectionFactory;
    /**
     * @var mixed
     */
    protected $resource;

    /**
     * MassDelete constructor.
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magento\Backend\App\Action\Context $context
     * @param mixed $permission
     * @param mixed $resource
     * @param mixed $collectionFactory
     */
    public function __construct(
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Backend\App\Action\Context $context,
        $permission,
        $resource,
        $collectionFactory
    ) {
        $this->filter     = $filter;
        $this->context    = $context;
        $this->permission = $permission;
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;

        parent::__construct($context);
    }

    /**
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->getRequest()->getParams()) {
            return $resultRedirect->setPath('*/*/');
        }

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $object) {
            $object->setIsMassDelete(true)->delete();
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $collectionSize));


        return $resultRedirect->setPath('*/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->context->getAuthorization()->isAllowed($this->permission);
    }
}
