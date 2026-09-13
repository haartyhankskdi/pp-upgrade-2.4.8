<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Block\Adminhtml;

use Magento\Store\Model\ScopeInterface;

/**
 * Step1 import block
 */
class Step2 extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magefan\BlogImport\Model\Csv
     */
    protected $csv;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magefan\Blog\Model\ResourceModel\Post $resource
     * @param \Magefan\BlogImport\Model\Csv $csv
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magefan\Blog\Model\ResourceModel\Post $resource,
        \Magefan\BlogImport\Model\Csv $csv,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->csv =$csv;
        $this->resource = $resource;
    }

    /**
     * Retrieves rows from the CSV with a predefined limit.
     *
     * @return array
     */
    public function getRows()
    {
        return $this->csv->getRows(15);
    }

    /**
     * Retrieve a list of column mappings for import operations.
     *
     * @return array
     */
    public function getColumns(): array
    {
        $tableInfo = $this->resource->getConnection()->describeTable(
            $this->resource->getMainTable()
        );

        $columns = ['' => 'Do not import'];
        foreach ($tableInfo as $field => $info) {
            if ($field === 'post_id') {
                $columns['existing_posts'] = __('ID of Existing Post');
            } else {
                $columns[$field] = ucwords(str_replace('_', ' ', $field));
            }
        }
        $columns['categories'] = 'Categories';
        $columns['tags'] = 'Tags';
        $columns['store_ids'] = 'Store IDs';
        $columns['related_posts'] = 'Related Posts';
        $columns['related_products'] = 'Related Products';

        return $columns;
    }
}
