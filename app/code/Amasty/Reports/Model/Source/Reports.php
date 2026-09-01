<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Source;

use Amasty\Reports\Model\ReportsDataProvider;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;

class Reports implements OptionSourceInterface
{
    /**
     * @var ReportsDataProvider
     */
    private $dataProvider;

    /**
     * @var array
     */
    private $excludedReports;

    public function __construct(
        ReportsDataProvider $dataProvider,
        array $excludedReports = []
    ) {
        $this->dataProvider = $dataProvider;
        $this->excludedReports = $excludedReports;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $reports = [];
        foreach ($this->dataProvider->getConfig() as $key => $data) {
            if (isset($data['children']) && !in_array($key, $this->excludedReports)) {
                $reports[] = [
                    'label' => $data['title'],
                    'value' => $this->prepareChildren($data['children'])
                ];
            }
        }

        return $reports;
    }

    private function prepareChildren(array $children): array
    {
        $childReports = [];
        foreach ($children as $value => $fieldData) {
            if (in_array($value, $this->excludedReports)) {
                continue;
            }

            if (isset($children['children']) && is_array($children['children'])) {
                $childReports = $this->prepareChildren($children['children']);
            } else {
                $childReports[] = [
                    'value' => (string)$value,
                    'label' => $fieldData['title']
                ];
            }
        }

        return $childReports;
    }

    /**
     * @param string $value
     * @return Phrase|string
     */
    public function getLabelByValue(string $value)
    {
        foreach ($this->dataProvider->getConfig() as $key => $data) {
            if ($label = $this->findLabel($value, $data['children'])) {
                break;
            }
        }

        return $label;
    }

    /**
     * @param string $value
     * @param array $children
     * @return Phrase|string|null
     */
    private function findLabel(string $value, array $children)
    {
        $title = null;
        foreach ($children as $key => $data) {
            if (isset($data['children']) && is_array($data['children'])) {
                $title = $this->findLabel($value, $data['children']);
            } elseif ($key == $value) {
                $title = $data['title'] ?? '';
                break;
            }
        }

        return $title;
    }
}
