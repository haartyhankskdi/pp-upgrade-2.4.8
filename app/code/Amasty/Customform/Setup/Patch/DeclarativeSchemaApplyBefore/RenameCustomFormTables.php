<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Setup\Patch\DeclarativeSchemaApplyBefore;

use Amasty\Customform\Model\ResourceModel\Answer;
use Amasty\Customform\Model\ResourceModel\Form;
use Magento\Framework\Setup\Patch\PatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class RenameCustomFormTables implements PatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    public function __construct(
        SchemaSetupInterface $schemaSetup
    ) {
        $this->schemaSetup = $schemaSetup;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): RenameCustomFormTables
    {
        $formTableName = $this->schemaSetup->getTable('am_customform_form');
        $answerTableName = $this->schemaSetup->getTable('am_customform_answer');
        $connection = $this->schemaSetup->getConnection();

        if ($connection->isTableExists($formTableName) && $connection->isTableExists($answerTableName)) {
            $newFormTableName = $this->schemaSetup->getTable(Form::TABLE);
            $newAnswerTableName = $this->schemaSetup->getTable(Answer::TABLE_NAME);
            $connection->renameTable($formTableName, $newFormTableName);
            $connection->renameTable($answerTableName, $newAnswerTableName);
        }

        return $this;
    }
}
