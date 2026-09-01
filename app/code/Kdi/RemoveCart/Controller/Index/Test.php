<?php
namespace Kdi\RemoveCart\Controller\Index;

class Test extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
        \Kdi\RemoveCart\Cron\RemoveCartItem $removeCart
    )
	{
		$this->_pageFactory = $pageFactory;
        $this->removeCart = $removeCart;
		return parent::__construct($context);
	}

	public function execute()
	{
		print_r($this->removeCart->execute());
		exit;
	}
}
