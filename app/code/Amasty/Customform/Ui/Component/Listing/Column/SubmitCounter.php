<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Amasty\Customform\Model\Config\Source\Status;

class SubmitCounter extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['total'] = [
                    'href' => $this->context->getUrl(
                        'amasty_customform/answer/index',
                        [
                            'form_id' => $item['form_id']
                        ]
                    ),
                    'label' => __('View All (%1)', $item['answers_count']),
                    'hidden' => false,
                ];

                $item[$this->getData('name')]['answered'] = [
                    'href' => $this->context->getUrl(
                        'amasty_customform/answer/index',
                        [
                            'form_id' => $item['form_id'],
                            'status' => Status::ANSWERED
                        ]
                    ),
                    'label' => __('Answered (%1)', $item['answered_count']),
                    'hidden' => false,
                ];

                $item[$this->getData('name')]['pending'] = [
                    'href' => $this->context->getUrl(
                        'amasty_customform/answer/index',
                        [
                            'form_id' => $item['form_id'],
                            'status' => Status::PENDING
                        ]
                    ),
                    'label' => __('Pending (%1)', $item['pending_count']),
                    'hidden' => false,
                ];
            }
        }

        return $dataSource;
    }
}
