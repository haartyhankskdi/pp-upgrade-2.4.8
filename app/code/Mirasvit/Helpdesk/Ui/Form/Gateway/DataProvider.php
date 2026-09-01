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


namespace Mirasvit\Helpdesk\Ui\Form\Gateway;

use Mirasvit\Helpdesk\Helper\Storeview;
use Mirasvit\Helpdesk\Model\ResourceModel\Gateway\CollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Gateway;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Mirasvit\Helpdesk\Controller\Adminhtml\Gateway\Save;

class DataProvider extends \Mirasvit\Helpdesk\Ui\Form\DataProvider
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var Gateway
     */
    private $gatewayResource;
    /**
     * @var Storeview
     */
    private $helpdeskStoreview;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @param Storeview $helpdeskStoreview
     * @param Gateway $gatewayResource
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
        Gateway $gatewayResource,
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
        $this->gatewayResource    = $gatewayResource;
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
            'helpdesk/gateway/save',
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
        /** @var \Mirasvit\Helpdesk\Model\Gateway $gateway */
        foreach ($this->getCollection() as $gateway) {
            $gateway->setData('password', Save::PASSWORD_PLACEHOLDER);
            $data[$gateway->getId()] = $gateway->getData();
        }

        return $data;
    }
}
