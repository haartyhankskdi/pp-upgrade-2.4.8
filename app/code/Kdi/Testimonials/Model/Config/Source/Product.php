<?php
namespace Kdi\Testimonials\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class Product implements ArrayInterface
{
    protected $_productCollection;
    protected $request;

    public function __construct(ProductCollection $productCollection, RequestInterface $requestInterface)
    {
        $this->_productCollection = $productCollection;
        $this->request = $requestInterface;
    }

    public function toOptionArray()
    {
        $entityId = (int) $this->request->getParam('category_id');

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->_productCollection->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('type_id', ['eq' => 'configurable'])
            ->setPageSize(100);

        if ($entityId) {
            $collection->joinTable(
                'catalog_category_product',
                'product_id=entity_id',
                ['category_id' => 'category_id'],
                null,
                'inner'
            );
            $collection->addFieldToFilter('category_id', $entityId);
        }

        $options = [];
        foreach ($collection as $product) {
            $options[] = [
                'value' => $product->getId(),
                'label' => $product->getName()
            ];
        }

        return $options;
    }
}