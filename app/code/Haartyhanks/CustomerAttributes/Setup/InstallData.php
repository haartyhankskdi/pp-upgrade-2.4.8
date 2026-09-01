<?php
namespace Haartyhanks\CustomerAttributes\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        /** @var $attributeSet AttributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
        
        $customerSetup->addAttribute(Customer::ENTITY, 'ethnicitycust', [
            'type' => 'varchar', // Store as text
        'label' => 'Ethnicity',
        'input' => 'text', // User enters text, but it's validated
        'required' => false,
        'visible' => true,
        'user_defined' => true,
        'position' => 100,
        'system' => 0,
        'is_used_in_grid' => 1,
        'is_visible_in_grid' => 1,
        'is_filterable_in_grid' => 1,
        'is_searchable_in_grid' => 1

        ]);

        $firstDropdownAttribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'ethnicitycust')
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer', 'customer_account_create', 'customer_account_edit'],
            ]);
        $firstDropdownAttribute->save();

        // Add Second Dropdown Attribute
        $customerSetup->addAttribute(Customer::ENTITY, 'sub_ethnicitycust', [
            'type' => 'varchar',
            'label' => 'Sub Ethnicity',
            'input' => 'text',
            'required' => false,
            'visible' => true,
            'user_defined' => true,
            'position' => 110,
            'system' => 0,
            'is_used_in_grid' => 1,
            'is_visible_in_grid' => 1,
            'is_filterable_in_grid' => 1,
            'is_searchable_in_grid' => 1,
        ]);

        $secondDropdownAttribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'sub_ethnicitycust')
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer', 'customer_account_create', 'customer_account_edit'],
            ]);
        $secondDropdownAttribute->save();
    }
}
