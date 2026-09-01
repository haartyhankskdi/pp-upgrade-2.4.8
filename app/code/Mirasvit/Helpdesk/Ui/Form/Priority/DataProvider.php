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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\Helpdesk\Ui\Form\Priority;

use Mirasvit\Helpdesk\Api\Data\PriorityInterface;
use Mirasvit\Helpdesk\Helper\Storeview;
use Mirasvit\Helpdesk\Model\ResourceModel\Priority\CollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Priority;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;

class DataProvider extends \Mirasvit\Helpdesk\Ui\Form\DataProvider
{
    /**
     * @var Storeview
     */
    private $helpdeskStoreview;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Priority
     */
    private $priorityResource;
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @param Storeview $helpdeskStoreview
     * @param Priority $priorityResource
     * @param CollectionFactory $collectionFactory
     * @param UrlInterface $url
     * @param RequestInterface $request
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        Storeview $helpdeskStoreview,
        Priority $priorityResource,
        CollectionFactory $collectionFactory,
        UrlInterface $url,
        RequestInterface $request,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->helpdeskStoreview  = $helpdeskStoreview;
        $this->priorityResource   = $priorityResource;
        $this->collection         = $collectionFactory->create();
        $this->url                = $url;
        $this->request            = $request;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigData()
    {
        $config = parent::getConfigData();

        $config['submit_url'] = $this->url->getUrl(
            'helpdesk/priority/save',
            [
                'id'    => (int) $this->request->getParam('id'),
                'store' => (int) $this->request->getParam('store'),
            ]
        );

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $data = [];
        /** @var \Mirasvit\Helpdesk\Model\Priority $priority */
        foreach ($this->getCollection() as $priority) {
            $this->priorityResource->afterLoad($priority);

            $data[$priority->getId()] = $priority->getData();

            $storeId = (int) $this->request->getParam('store');
            $priority->setStoreId($storeId);

            $name  = $this->helpdeskStoreview->getStoreViewValue($priority, PriorityInterface::KEY_NAME);
            $data[$priority->getId()][PriorityInterface::KEY_NAME] = $name;

            $priority->unsetData('store_id');
        }
        return $data;
    }
}
