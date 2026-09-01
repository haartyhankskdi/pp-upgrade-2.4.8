<?php
namespace Kdi\JumioVerification\Model;

use Magento\Framework\Model\AbstractModel;
use Kdi\JumioVerification\Api\Data\JumioInterface;

class JumioVerification extends AbstractModel
{
    const CACHE_TAG = 'jumio_verification';

	protected $_cacheTag = 'jumio_verification';

	protected $_eventPrefix = 'jumio_verification';

    protected function _construct()
    {
        $this->_init('Kdi\JumioVerification\Model\ResourceModel\JumioVerification');
    }

    public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}

}
