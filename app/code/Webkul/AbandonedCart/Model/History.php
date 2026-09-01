<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\AbandonedCart\Model;

use Webkul\AbandonedCart\Api\Data\HistoryInterface;
use Magento\Framework\DataObject\IdentityInterface as Identity;
use Magento\Framework\Model\AbstractModel;

class History extends AbstractModel implements HistoryInterface, Identity
{
    /**
     * No route page id
     */
    const NOROUTE_ENTITY_ID = 'no-route';

    /**
     * ProductDetail Gallery cache tag
     */
    const CACHE_TAG = 'wk_abandoned_cart_mails';

    /**
     * @var string
     */
    protected $_cacheTag = 'wk_abandoned_cart_mails';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'wk_abandoned_cart_mails';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init(\Webkul\AbandonedCart\Model\ResourceModel\History::class);
    }

    /**
     * Load object data.
     *
     * @param int|null $id
     * @param string   $field
     *
     * @return $this
     */
    public function load($id, $field = null)
    {
        if ($id === null) {
            return $this->noRouteItem();
        }

        return parent::load($id, $field);
    }

    /**
     * Load No-Route Item.
     *
     * @return \Webkul\AbandonedCart\Model\History
     */
    public function noRouteItem()
    {
        return $this->load(self::NOROUTE_ENTITY_ID, $this->getIdFieldName());
    }

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
     * Get ID.
     *
     * @return int
     */
    public function getId()
    {
        return parent::getData(self::ENTITY_ID);
    }

    /**
     * Set ID.
     *
     * @param int $id
     *
     * @return \Webkul\AbandonedCart\Api\Data\HistoryInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }
}
