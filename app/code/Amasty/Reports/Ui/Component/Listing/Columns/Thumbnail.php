<?php

declare(strict_types=1);

namespace Amasty\Reports\Ui\Component\Listing\Columns;

use Magento\Catalog\Helper\Image;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Thumbnail extends Column
{
    public const NAME = 'thumbnail';

    public const ALT_FIELD = 'name';

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Image $imageHelper,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->imageHelper = $imageHelper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $productObject = new \Magento\Framework\DataObject($item);
                $imageHelper = $this->imageHelper->init($productObject, 'product_listing_thumbnail');
                $item[$fieldName . '_src'] = $imageHelper->getUrl();
                $item[$fieldName . '_alt'] = $this->getItemAlt($item) ?: $imageHelper->getLabel();
                $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                    'catalog/product/edit',
                    [
                        'id' => $productObject->getEntityId() ?: $productObject->getProductId(),
                        'store' => $this->context->getRequestParam('store')
                    ]
                );
                $imageHelperPreview = $this->imageHelper->init($productObject, 'product_listing_thumbnail_preview');
                $item[$fieldName . '_orig_src'] = $imageHelperPreview->getUrl();
                $item['thumbnail_link'] = $this->urlBuilder->getUrl(
                    'catalog/product/edit',
                    [
                        'id' => (int) $productObject->getEntityId() ?: $productObject->getProductId(),
                        'store' => $this->context->getRequestParam('store')
                    ]
                );
            }
        }

        return $dataSource;
    }

    private function getItemAlt(array $item): ?string
    {
        $altField = $this->getData('config/altField') ?: self::ALT_FIELD;
        return $item[$altField] ?? null;
    }
}
