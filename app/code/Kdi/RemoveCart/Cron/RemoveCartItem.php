<?php


namespace Kdi\RemoveCart\Cron;

class RemoveCartItem {

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;
 
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $quoteCollectionFactory;
 
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {
 
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->quoteRepository = $quoteRepository;
    }
    public function execute()
    {
        $fromTime = new \DateTime('now', new \DateTimezone('UTC'));
        $fromTime->sub(\DateInterval::createFromDateString('30 minutes'));
 
        $fromDate = $fromTime->format('Y-m-d H:i:s');
        $quoteCollection = $this->quoteCollectionFactory->create();
 
        $quoteCollection
            ->addFieldToFilter('created_at', ['lteq' => $fromDate]);
 
        if($quoteCollection->getSize() >0){
            foreach ($quoteCollection as $quote)
            {
                $quoteFullObject = $this->quoteRepository->get($quote->getId());
                $quoteFullObject->delete();
            }
        }
 
 
    }
}