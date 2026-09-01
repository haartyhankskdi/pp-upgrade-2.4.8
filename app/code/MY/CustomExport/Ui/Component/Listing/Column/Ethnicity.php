<?php
namespace MY\CustomExport\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class Ethnicity extends Column
{
    protected $customerFactory;

    /**
     * Constructor
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CustomerFactory $customerFactory,
        array $components = [],
        array $data = []
    ) {
        $this->customerFactory = $customerFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                // Get Customer ID from order data
                $customerId = $item['customer_id'] ?? null;

                if ($customerId) {
                    // Load Customer Data if the column value is empty
                    $customer = $this->customerFactory->create()->load($customerId);

                    if (empty($item[$this->getData('name')])) {
                        $item[$this->getData('name')] = $customer->getData($this->getData('name')) ?: ''; // Leave blank if empty
                    }
                }
            }
        }
        return $dataSource;
    }
}
