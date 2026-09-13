<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Mage 2.4.8 Fix
 */

declare(strict_types=1);

namespace Amasty\Mage248Fix\Ui\Component\Design\Config;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Ui\Component\Design\Config\DataProvider as OriginalUiConfigDataProvider;

class DataProvider extends OriginalUiConfigDataProvider
{
    /**
     * @var StoreManagerInterface
     * @since 100.1.0
     */
    protected $storeManager;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     * @param ResourceConnection|null $resourceConnection
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = [],
        ?ResourceConnection $resourceConnection = null
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $storeManager,
            $meta,
            $data,
            $resourceConnection
        );
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection ?: ObjectManager::getInstance()->get(ResourceConnection::class);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if ($this->storeManager->isSingleStoreMode()) {
            $websites = $this->storeManager->getWebsites();
            $singleStoreWebsite = array_shift($websites);

            $this->addFilter(
                $this->filterBuilder->setField('store_website_id')
                    ->setValue($singleStoreWebsite->getId())
                    ->create()
            );
            $this->addFilter(
                $this->filterBuilder->setField('store_group_id')
                    ->setConditionType('null')
                    ->create()
            );
        }

        $themeConfigData = $this->getCoreConfigData();
        $data = BaseDataProvider::getData();
        foreach ($data['items'] as & $item) {
            $item += ['default' => __('Global')];

            $scope = ($item['store_id']) ? 'stores' : (($item['store_website_id']) ? 'websites' : 'default');
            $scopeId = (int)$item['store_website_id'] ?? 0;
            $themeId = (int)$item['theme_theme_id'] ?? 0;

            $criteria = ['scope' => $scope, 'scope_id' => $scopeId, 'value' => $themeId];
            $configData = array_filter($themeConfigData, function ($themeConfig) use ($criteria) {
                return array_intersect_assoc($criteria, $themeConfig) === $criteria;
            });

            $item += ['short_description' => !$configData ? __('Using Default Theme') : ''];
        }

        return $data;
    }

    private function getCoreConfigData(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('core_config_data');

        return $connection->fetchAll(
            $connection->select()->from($tableName)
                ->where('path = ?', 'design/theme/theme_id')
        );
    }
}
