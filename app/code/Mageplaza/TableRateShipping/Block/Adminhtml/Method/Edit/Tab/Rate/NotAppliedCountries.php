<?php

namespace Mageplaza\TableRateShipping\Block\Adminhtml\Method\Edit\Tab\Rate;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

/**
 * Class NotAppliedCountries
 * @package Mageplaza\TableRateShipping\Block\Adminhtml\Method\Edit\Tab\Rate
 */
class NotAppliedCountries extends AbstractRenderer
{
    
    /**
     * Render column
     *
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        // Get the value of the "not_applied_countries" column from the row object
        $notAppliedCountries = $row->getData('not_applied_countries');

        // Check if the value is an array
        if (is_array($notAppliedCountries) && count($notAppliedCountries) > 0) {
            // Format the data as a comma-separated list
            $formattedData = implode(', ', $notAppliedCountries);
        } else {
            // If the data is not an array or is empty, display a default message
            $formattedData = __('None');
        }

        // Return the formatted data to be displayed in the grid column
        return $notAppliedCountries;
    }
}