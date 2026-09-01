<?php
declare(strict_types=1);

namespace Haartyhanks\LNAPI\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class AddLnCustomerVerificationAttributes implements DataPatchInterface, PatchRevertableInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private CustomerSetupFactory $customerSetupFactory;
    private SetFactory $attributeSetFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        SetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create([
            'setup' => $this->moduleDataSetup
        ]);

        $customerEntity = $customerSetup->getEavConfig()
            ->getEntityType(Customer::ENTITY);

        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        /** @var Set $attributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $attributes = [
            'ln_kyc' => 'LN KYC Completed',
            'age_verified' => 'Age Verified',
            'document_assessment' => 'Document Assessment',
            'facial_match' => 'Facial Match',
            'liveness' => 'Liveness Check'
        ];

        foreach ($attributes as $code => $label) {
            $customerSetup->addAttribute(
                Customer::ENTITY,
                $code,
                [
                    'label' => $label,
                    'type' => 'int',
                    'input' => 'boolean',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'required' => false,
                    'visible' => true,
                    'system' => false,
                    'default' => 0,
                    'position' => 300,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $attribute = $customerSetup->getEavConfig()
                ->getAttribute(Customer::ENTITY, $code);

            $attribute->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => [
                    'adminhtml_customer',
                    'customer_account_edit'
                ]
            ]);

            $attribute->save();
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create([
            'setup' => $this->moduleDataSetup
        ]);

        foreach ([
            'ln_kyc',
            'age_verified',
            'document_assessment',
            'facial_match',
            'liveness'
        ] as $code) {
            $customerSetup->removeAttribute(Customer::ENTITY, $code);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }
}
