<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue;

use Amasty\Customform\Helper\Messages;
use Amasty\Customform\Model\Utils\CustomerInfo;
use Magento\Customer\Model\Customer;

class CustomerFieldsProvider implements ProviderInterface
{
    public const CUSTOMER_FIELDS = [
        Messages::CUSTOMER_FIRST_NAME,
        Messages::CUSTOMER_LAST_NAME,
        Messages::CUSTOMER_CITY,
        Messages::CUSTOMER_COMPANY,
        Messages::CUSTOMER_EMAIL,
        Messages::CUSTOMER_PHONE_NUMBER,
        Messages::CUSTOMER_POST_CODE,
        Messages::CUSTOMER_REGION,
        Messages::CUSTOMER_STREET_ADDRESS,
    ];

    /**
     * @var string[]
     */
    private $acceptableVariables;

    /**
     * @var CustomerInfo
     */
    private $customerInfo;

    public function __construct(
        CustomerInfo $customerInfo,
        array $acceptableVariables = self::CUSTOMER_FIELDS
    ) {
        $this->acceptableVariables = array_map(function (string $variable) {
            return trim($variable, '{}');
        }, $acceptableVariables);
        $this->customerInfo = $customerInfo;
    }

    public function isCanRetrieve(string $variableName): bool
    {
        return in_array($variableName, $this->acceptableVariables) && $this->customerInfo->isLoggedIn();
    }

    public function getValue(string $variableName): string
    {
        return $this->getCustomerValue(
            $this->customerInfo->getCurrentCustomer(),
            $variableName
        );
    }

    public function getCustomerValue(Customer $customer, string $fieldName): string
    {
        $variable = sprintf('{%s}', $fieldName);
        $addressFields = [
            Messages::CUSTOMER_COMPANY,
            Messages::CUSTOMER_CITY,
            Messages::CUSTOMER_POST_CODE,
            Messages::CUSTOMER_REGION,
            Messages::CUSTOMER_STREET_ADDRESS,
            Messages::CUSTOMER_PHONE_NUMBER,
        ];
        $value = in_array($variable, $addressFields)
            ? $this->getAddressValue($customer, $variable, $fieldName)
            : (string) $customer->getData($fieldName);

        return $value ?: '';
    }

    private function getAddressValue(Customer $customer, string $fieldName, string $fieldCode): string
    {
        $address = $customer->getDefaultBillingAddress();

        if (!$address) {
            return '';
        }

        switch ($fieldName) {
            case Messages::CUSTOMER_STREET_ADDRESS:
                $value = preg_replace('/[\n]/', ' - ', $address->getData($fieldCode));
                break;
            case Messages::CUSTOMER_PHONE_NUMBER:
                $value = preg_replace("/[^0-9]/", "", $address->getData($fieldCode));
                break;
            default:
                $value = $address->getData($fieldCode);
        }

        return (string) $value;
    }
}
