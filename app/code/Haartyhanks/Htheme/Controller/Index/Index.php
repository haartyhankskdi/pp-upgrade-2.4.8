<?php
namespace Haartyhanks\Htheme\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{

     protected $resultPageFactory;


    public function __construct(Context $context,
     \Magento\Framework\View\Result\PageFactory $resultPageFactory
 )
    {
         $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
