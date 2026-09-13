<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Model\Indexer\Product;

use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\Indexer\AbstractIndexer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Mageplaza\PromoBanner\Model\Indexer\Indexer;

/**
 * Class Banner
 * @package Mageplaza\PromoBanner\Model\Indexer\Product
 */
class Banner extends AbstractIndexer
{
    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * Banner constructor.
     *
     * @param Indexer $indexBuilder
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Indexer $indexBuilder,
        ManagerInterface $eventManager
    ) {
        parent::__construct($indexBuilder, $eventManager);
    }

    /**
     * @param int[] $ids
     *
     * @throws LocalizedException
     */
    protected function doExecuteList($ids)
    {
        $this->indexBuilder->reindexByIds(array_unique($ids));
        $this->getCacheContext()->registerEntities(Product::CACHE_TAG, $ids);
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecuteRow($id)
    {
        $this->indexBuilder->reindexById($id);
    }
}
