<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Model\ResourceModel;

use Magefan\Blog\Model\ResourceModel\Post;

class PostPlugin
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter
     */
    protected $customerGroupFilter;

    /**
     * PostPlugin constructor.
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
    ) {
        $this->customerGroupFilter = $customerGroupFilter;
        $this->request = $request;
    }

    /**
     * Processes and formats link data before saving the resource model.
     *
     * @param Post $resourceModel
     * @param mixed $subject
     * @return void
     */
    public function beforeSave(Post $resourceModel, $subject): void
    {
        $data = $this->request->getPost('data');
        if (!isset($data['links'])) {
            return;
        }

        $links = $data['links'];
        if (is_array($links)) {
            foreach (['product'] as $linkType) {
                if (isset($links[$linkType]) && is_array($links[$linkType])) {
                    $linksData = [];
                    $keys = [
                        'position',
                        'display_on_product',
                        'display_on_post',
                        'auto_related'
                    ];

                    foreach ($links[$linkType] as $item) {
                        $linksData[$item['id']] = [];
                        foreach ($keys as $key) {
                            $linksData[$item['id']][$key] = isset($item[$key]) ? $item[$key] : 0;
                        }
                    }

                    $links[$linkType] = $linksData;
                } else {
                    $links[$linkType] = [];
                }
            }

            foreach (['post'] as $linkType) {
                if (isset($links[$linkType]) && is_array($links[$linkType])) {
                    $linksData = [];
                    foreach ($links[$linkType] as $item) {
                        $linksData[$item['id']]['auto_related'] =
                            isset($item['auto_related']) ? $item['auto_related'] : 0;
                        $linksData[$item['id']]['position'] =
                            isset($item['position']) ? $item['position'] : 0;
                    }
                    $links[$linkType] = $linksData;
                } else {
                    $links[$linkType] = [];
                }
            }

            $pLinks = $subject->getData('links');
            $pLinks['post'] = $links['post'];
            $pLinks['product'] = $links['product'];

            $subject->setData('links', $pLinks);
        }
    }

    /**
     * Executes additional processing after loading the resource model.
     *
     * @param Post $resourceModel
     * @param mixed $result
     * @param mixed $subject
     * @return mixed
     */
    public function afterLoad(Post $resourceModel, $result, $subject)
    {
        $this->customerGroupFilter->loadGroupFilter($subject);

        return $result;
    }

    /**
     * Executes additional operations after saving the resource model.
     *
     * @param Post $resourceModel
     * @param mixed $result
     * @param mixed $subject
     * @return mixed
     */
    public function afterSave(Post $resourceModel, $result, $subject)
    {
        $this->customerGroupFilter->saveGroupFilter($subject);

        return $result;
    }

    /**
     * Executes additional operations after deleting the resource model.
     *
     * @param Post $resourceModel
     * @param mixed $result
     * @param mixed $subject
     * @return mixed
     */
    public function afterDelete(Post $resourceModel, $result, $subject)
    {
        $this->customerGroupFilter->deleteGroupFilter($subject);
        return $result;
    }
}
