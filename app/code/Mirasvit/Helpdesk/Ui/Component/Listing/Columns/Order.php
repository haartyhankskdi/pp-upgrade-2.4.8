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



namespace Mirasvit\Helpdesk\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Api\Data\OrderInterface;

class Order extends Column
{
    const URL_PATH = 'sales/order/view/order_id/';

    protected $context;

    protected $uiComponentFactory;

    protected $urlBuilder;

    protected $order;

    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface       $urlBuilder,
        OrderInterface     $order,
        array              $components = [],
        array              $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->order      = $order;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['order_id']) && $item['order_id']) {
                    $id                           = $item['order_id'];
                    $incrementId                  = $this->order->load($id)->getIncrementId();
                    $item[$this->getData('name')] = [
                        'new' => [
                            'href'  => $this->urlBuilder->getUrl(
                                static::URL_PATH . $id
                            ),
                            'label' => __($incrementId),
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
