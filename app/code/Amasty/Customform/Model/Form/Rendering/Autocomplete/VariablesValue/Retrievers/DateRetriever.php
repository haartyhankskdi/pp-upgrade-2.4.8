<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue\Retrievers;

use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class DateRetriever implements RetrieverInterface
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(
        TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
    }

    /**
     * Retrieve formatted date time attribute value
     *
     * @param AttributeInterface $attribute
     * @param string $value
     * @return string
     */
    public function retrieve(AttributeInterface $attribute, string $value): string
    {
        return $this->timezone->formatDateTime(
            $value,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::NONE
        );
    }
}
