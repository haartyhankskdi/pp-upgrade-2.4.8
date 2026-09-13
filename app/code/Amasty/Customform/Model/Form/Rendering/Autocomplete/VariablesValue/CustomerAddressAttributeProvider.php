<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue;

use Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue\Retrievers\RetrieverInterface;
use Amasty\Customform\Model\Utils\CustomerInfo;
use Amasty\Customform\Model\Utils\MagentoEdition;

class CustomerAddressAttributeProvider implements ProviderInterface
{
    /**
     * @var MagentoEdition
     */
    private $magentoEdition;

    /**
     * @var CustomerInfo
     */
    private $customerInfo;

    /**
     * @var RetrieverInterface
     */
    private $attributeValueRetriever;

    public function __construct(
        MagentoEdition $magentoEdition,
        CustomerInfo $customerInfo,
        RetrieverInterface $attributeValueRetriever
    ) {
        $this->magentoEdition = $magentoEdition;
        $this->customerInfo = $customerInfo;
        $this->attributeValueRetriever = $attributeValueRetriever;
    }

    public function isCanRetrieve(string $variableName): bool
    {
        if ($this->magentoEdition->isEnterpriseVersion()
            && $this->customerInfo->isLoggedIn()
        ) {
            $address = $this->customerInfo->getCurrentCustomer()->getDefaultBillingAddress();
            $addressAttributes = $address->getAttributes();

            return isset($addressAttributes[$variableName]);
        }

        return false;
    }

    public function getValue(string $variableName): string
    {
        $address = $this->customerInfo->getCurrentCustomer()->getDefaultBillingAddress();
        $addressAttributes = $address->getAttributes();
        $customAttribute = $address->getCustomAttribute($variableName);
        $customAttributeValue = $customAttribute ? $customAttribute->getValue() : $address->getData($variableName);

        return $customAttributeValue === null || $customAttributeValue === ''
            ? ''
            : $this->attributeValueRetriever->retrieve($addressAttributes[$variableName], $customAttributeValue);
    }
}
