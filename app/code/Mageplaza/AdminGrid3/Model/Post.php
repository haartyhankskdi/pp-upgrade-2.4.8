<?php

namespace Mageplaza\AdminGrid3\Model;

class Post extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'jumio_verification';

	protected $_cacheTag = 'jumio_verification';

	protected $_eventPrefix = 'jumio_verification';

	protected function _construct()
	{
		$this->_init('Mageplaza\AdminGrid3\Model\ResourceModel\Post');
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


?>