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
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Mirasvit\Helpdesk\Api\Data\TagInterface;

/**
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Tag\Collection|\Mirasvit\Helpdesk\Model\Tag[] getCollection()
 * @method \Mirasvit\Helpdesk\Model\Tag load(int $id)
 * @method bool getIsMassDelete()
 * @method \Mirasvit\Helpdesk\Model\Tag setIsMassDelete(bool $flag)
 * @method bool getIsMassStatus()
 * @method \Mirasvit\Helpdesk\Model\Tag setIsMassStatus(bool $flag)
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Tag getResource()
 */
class Tag extends \Magento\Framework\Model\AbstractModel implements IdentityInterface, TagInterface
{
    const CACHE_TAG = 'helpdesk_tag';

    /**
     * @var string
     */
    protected $_cacheTag = 'helpdesk_tag';

    /**
     * @var string
     */
    protected $_eventPrefix = 'helpdesk_tag';

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\Helpdesk\Model\ResourceModel\Tag');
    }

    /**
     * @param bool $emptyOption
     *
     * @return array
     */
    public function toOptionArray($emptyOption = false)
    {
        return $this->getCollection()->toOptionArray($emptyOption);
    }

    /**
     * @return int
     */
    public function getTagId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @param int $tagId
     * @return $this
     */
    public function setTagId($tagId)
    {
        return $this->setData(self::ID, $tagId);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::KEY_NAME);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::KEY_NAME, $name);
    }
}
