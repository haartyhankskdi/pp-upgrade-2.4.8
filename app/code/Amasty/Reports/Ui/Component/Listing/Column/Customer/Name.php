<?php

namespace Amasty\Reports\Ui\Component\Listing\Column\Customer;

use Magento\Ui\Component\Listing\Columns\Column;

class Name extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = empty($item[$this->getData('name')])
                    ? __('Guest')
                    : $item[$this->getData('name')];
            }
        }

        return $dataSource;
    }
}
