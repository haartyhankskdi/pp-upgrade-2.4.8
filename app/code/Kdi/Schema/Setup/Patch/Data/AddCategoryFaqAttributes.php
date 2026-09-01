<?php
declare(strict_types=1);

namespace Kdi\Schema\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Category;

class AddCategoryFaqAttributes implements DataPatchInterface, PatchRevertableInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        for ($i = 1; $i <= 10; $i++) {
            // FAQ Question
            $eavSetup->addAttribute(
                Category::ENTITY,
                "faq_question_$i",
                [
                    'type' => 'text',
                    'label' => "FAQ Question $i",
                    'input' => 'textarea',
                    'required' => false,
                    'sort_order' => 100 + $i * 2,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'FAQ',
                    'visible' => true,
                    'user_defined' => true,
                    'default' => '',
                ]
            );

            // FAQ Answer
            $eavSetup->addAttribute(
                Category::ENTITY,
                "faq_answer_$i",
                [
                    'type' => 'text',
                    'label' => "FAQ Answer $i",
                    'input' => 'textarea',
                    'required' => false,
                    'sort_order' => 101 + $i * 2,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'FAQ',
                    'visible' => true,
                    'user_defined' => true,
                    'default' => '',
                ]
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        for ($i = 1; $i <= 10; $i++) {
            $eavSetup->removeAttribute(Category::ENTITY, "faq_question_$i");
            $eavSetup->removeAttribute(Category::ENTITY, "faq_answer_$i");
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
