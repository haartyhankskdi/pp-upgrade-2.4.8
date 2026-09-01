<?php
declare(strict_types=1);

namespace Nilesh\Reorder\Controller\Adminhtml\Ajax;

use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Nilesh\Reorder\Helper\Mail;

class Reorder extends \Magento\Backend\App\Action
{
  /**
   * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
   */
  protected $collectionFactory;
  /**
   * @var Filter
   */
  protected $filter;
  /**
   * @var \Magento\Sales\Api\OrderRepositoryInterface
   */
  protected $orderRepository;
  protected $redirectUrl = '*/*/';
  protected $helperMail;
  /**
   * Preparation constructor.
   * @param Context $context
   * @param Filter $filter
   * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
   * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
   */
  public function __construct(
      Context $context,
      Filter $filter,
      \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
      \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
      Mail $helperMail
  ) {
      parent::__construct($context);
      $this->collectionFactory = $orderCollectionFactory;
      $this->orderRepository = $orderRepository;
      $this->filter = $filter;
      $this->helperMail = $helperMail;
  }
  public function execute()
  {
      try {
          $collection = $this->filter->getCollection($this->collectionFactory->create());
          //mass action
          $countPreparationOrder = 0;
          /** @var \Magento\Sales\Model\Order $order */
          foreach ($collection->getItems() as $order) {
            if(!$order->getCustomerIsGuest()){
                // $email = $order->getBillingAddress()->getEmail();
                $email = $order->getCustomerEmail();
                $tempVar = array(
                    'order' => $order,
                    'billing' => $order->getBillingAddress(),
                    'store' => $order->getStore()
                );
                $this->helperMail->sendReorderTemplateEmail($email,$tempVar);
                $countPreparationOrder++;
            }
          }
          $countNonPreparationOrder = $collection->count() - $countPreparationOrder;
          if ($countNonPreparationOrder && $countPreparationOrder) {
              $this->messageManager->addErrorMessage(__('%1 order(s) cannot be reorder. We cannot send reorder to guest user', $countNonPreparationOrder));
          } elseif ($countNonPreparationOrder) {
              $this->messageManager->addErrorMessage(__('You cannot reorder the order(s).'));
          }
          if ($countPreparationOrder) {
              $this->messageManager->addSuccessMessage(__('We send reorder email for %1 order(s).', $countPreparationOrder));
          }
          $resultRedirect = $this->resultRedirectFactory->create();
          $resultRedirect->setPath($this->filter->getComponentRefererUrl() ?: 'sales/*/');
          return $resultRedirect;
      } catch (\Exception $e) {
          $this->messageManager->addErrorMessage($e->getMessage());
          /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
          $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
          return $resultRedirect->setPath($this->redirectUrl);
      }
  }
}