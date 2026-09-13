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


namespace Mirasvit\Helpdesk\Ui\Form\Gateway;

use Mirasvit\Helpdesk\Model\ResourceModel\Gateway\CollectionFactory;
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
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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
        CollectionFactory $collectionFactory,
        UrlInterface $url,
        RequestInterface $request,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
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
    public function getMeta()
    {
        $meta = parent::getMeta();

        // Build the OAuth connect URL server-side so it carries the admin secret key
        // (required when "Add Secret Key to URLs" is enabled). The secret key can only be
        // generated server-side, so the button must not hand-build this URL in JS.
        $meta['general']['children']['oauth_connect_button']['arguments']['data']['config']['connectUrl'] =
            $this->url->getUrl(
                'helpdesk/gateway_oauth/connect',
                ['id' => (int) $this->request->getParam('id')]
            );

        return $meta;
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
            $gateway->setData('client_secret', Save::PASSWORD_PLACEHOLDER);
            $data[$gateway->getId()] = $gateway->getData();
        }

        return $data;
    }
}
