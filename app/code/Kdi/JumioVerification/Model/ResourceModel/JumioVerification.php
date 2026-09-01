<?php
namespace Kdi\JumioVerification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class JumioVerification extends AbstractDb
{
    public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}
    
    protected function _construct()
    {
        $this->_init('jumio_verification', 'id');
    }
}
