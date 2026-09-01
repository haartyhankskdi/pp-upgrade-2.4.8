<?php
namespace Kdi\JumioVerification\Model\ResourceModel\JumioVerification;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'jumio_verification_collection';
    protected $_eventObject = 'verification_collection';

    /**
	 * Define resource model
	 *
	 * @return void
	 */
    protected function _construct()
    {
        $this->_init(
            'Kdi\JumioVerification\Model\JumioVerification',
            'Kdi\JumioVerification\Model\ResourceModel\JumioVerification'
        );
    }
}
