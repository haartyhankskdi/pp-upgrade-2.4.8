<?php

namespace Haartyhanks\CategoryQuestWL\Block\Adminhtml\Order\View;

use Amasty\Customform\Model\ResourceModel\Answer\CollectionFactory as AnswerCollectionFactory;

class View extends \Magento\Backend\Block\Template
{
    /**
     * @var AnswerCollectionFactory
     */
    protected $answerCollectionFactory;

    protected $itemFactory;

    public function __construct(
        AnswerCollectionFactory $answerCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->answerCollectionFactory = $answerCollectionFactory;
        $this->_coreRegistry = $registry;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->orderRepository = $orderRepository;
        $this->itemFactory = $itemFactory;
        parent::__construct($context, $data);
    }

    public function myFunction()
    {
        //your code
        return "Category Questionnaire";
    }

    public function getOrderId()
    {
        $order = $this->_coreRegistry->registry('current_order');
        return $order->getEntityId();
    }
    public function getAnserCollection()
    {
        $collection = $this->answerCollectionFactory->Create();
        return $collection;
    }

    // public function getAnswerCollectionByHashKey($questionnaireUniqueId)
    // {
    //     $collection = $this->answerCollectionFactory->Create()->addFieldToFilter('questionnaire_unique_id', $questionnaireUniqueId);
    //     return $collection->getData();
    // }
    public function getQuestionnaireHashKey()
    {
        try {
            $order = $this->orderRepository->get($this->getOrderId());
            $hashKey = $order->getQuestionnaireUniqueId();
        } catch (NoSuchEntityException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('This Hash Key no longer exists.'));
        }
        return $hashKey;
    }


    public function getOrderDetails($orderId)
    {
        return $this->orderRepository->get($this->getOrderId());
    }

    // public function getItemCollection()
    // {
    //     try {    
    //         $order = $this->itemFactory->create()->getCollection()->addFieldToFilter('order_id', $this->getOrderId());
    //         foreach ($order as $items) {
    //             echo $items->getItemId();  // similarly you can get all the values from slaes_order_item table
    //             echo $items->getQuestionnaireUniqueId();
    //             echo $items->getQty();
    //         }
    //     } catch (\Exception $e) {
    //         error_log($e->getMessage());
    //     }
    // }
}
