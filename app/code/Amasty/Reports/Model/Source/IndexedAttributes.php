<?php

namespace Amasty\Reports\Model\Source;

use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Catalog\Api\Data\EavAttributeInterface as EavAttribute;

class IndexedAttributes
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        AttributeRepository $attributeRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @return array
     */
    public function getAttributesOptions()
    {
        $searchCriteria = $this->prepareSearchCriteria();
        $collection = $this->attributeRepository->getList($searchCriteria);
        $attributesOptions[] = ['label' => __('Choose Attribute'), 'value' => 'NULL'];

        foreach ($collection->getItems() as $item) {
            $attributesOptions[] = ['label' => $item->getFrontendLabel(), 'value' => $item->getAttributeCode()];
        }

        return $attributesOptions;
    }

    /**
     * @return \Magento\Framework\Api\SearchCriteria
     */
    protected function prepareSearchCriteria()
    {
        return $this->searchCriteriaBuilder
            ->setFilterGroups([
                $this->getAttributePropertyFilterGroup(),
                $this->getAttributeGroup([
                    $this->getAttributeFilter(
                        'main_table.' . Set::KEY_ENTITY_TYPE_ID,
                        'eq',
                        CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID
                    )
                ]),
                $this->getAttributeGroup([
                    $this->getAttributeFilter(
                        'backend_type',
                        'in',
                        ['varchar', 'int', 'decimal']
                    )
                ]),
                $this->getAttributeGroup([
                    $this->getAttributeFilter(
                        'frontend_input',
                        'in',
                        ['multiselect', 'select']
                    )
                ])
            ])->create();
    }

    /**
     * @return \Magento\Framework\Api\AbstractSimpleObject
     */
    private function getAttributePropertyFilterGroup()
    {
        $isFilterableInSearch =
            $this->getAttributeFilter(EavAttribute::IS_FILTERABLE_IN_SEARCH, 'neq', '0');
        $isVisibleInAdvancedSearch =
            $this->getAttributeFilter(EavAttribute::IS_VISIBLE_IN_ADVANCED_SEARCH, 'neq', '0');
        $isFilterable =
            $this->getAttributeFilter(EavAttribute::IS_FILTERABLE, 'neq', '0');
        $attributeCodeFilter =
            $this->getAttributeFilter(EavAttribute::ATTRIBUTE_CODE, 'eq', 'visibility');

        return $this->getAttributeGroup([
            $isFilterableInSearch, $isVisibleInAdvancedSearch, $isFilterable, $attributeCodeFilter
        ]);
    }

    /**
     * @param string $field
     * @param string $condition
     * @param mixed $value
     * @return \Magento\Framework\Api\Filter
     */
    private function getAttributeFilter(string $field, string $condition, $value)
    {
        return $this->filterBuilder->setField($field)->setConditionType($condition)->setValue($value)->create();
    }

    /**
     * @param \Magento\Framework\Api\Filter[] $filters
     * @return \Magento\Framework\Api\AbstractSimpleObject
     */
    private function getAttributeGroup(array $filters)
    {
        return $this->filterGroupBuilder->setFilters($filters)->create();
    }
}
