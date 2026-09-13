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
use Magento\Customer\Model\Attribute;

class CustomerAttributeProvider implements ProviderInterface
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
            return $this->customerInfo->getCurrentCustomer()->getAttribute($variableName) !== null;
        }

        return false;
    }

    public function getValue(string $variableName): string
    {
        $customerDataModel = $this->customerInfo->getCustomerDataModel();
        $customer = $this->customerInfo->getCurrentCustomer();
        $customAttribute = $customerDataModel->getCustomAttribute($variableName);
        $customAttributeValue = $customAttribute ? $customAttribute->getValue() : $customer->getData($variableName);
        /** @var Attribute $customerAttribute **/
        $customerAttribute = $customer->getAttribute($variableName);

        return $customAttributeValue === null || $customAttributeValue === ''
            ? ''
            : $this->attributeValueRetriever->retrieve($customerAttribute, $customAttributeValue);
    }
}
