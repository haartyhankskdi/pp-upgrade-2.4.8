<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\AbandonedCart\Model;

use Magento\Framework\Data\OptionSourceInterface;

class CronStatus implements OptionSourceInterface
{
    /**
     * Options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => __("Cron"),'value' => 1];
        $options[] = ['label' => __("Manual"),'value' => 2];
        return $options;
    }
}
