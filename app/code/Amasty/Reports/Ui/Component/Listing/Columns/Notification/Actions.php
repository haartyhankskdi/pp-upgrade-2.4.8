<?php

declare(strict_types=1);

namespace Amasty\Reports\Ui\Component\Listing\Columns\Notification;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $item[$name]['edit'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'amasty_reports/notification/edit',
                        ['id' => $item['entity_id']]
                    ),
                    'label' => __('Edit')
                ];
                $item[$name]['delete'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'amasty_reports/notification/delete',
                        ['id' => $item['entity_id']]
                    ),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Notifications'),
                        'message' => __('Are you sure you wan\'t to delete selected Notifications?')
                    ]
                ];
            }
        }

        return $dataSource;
    }
}
