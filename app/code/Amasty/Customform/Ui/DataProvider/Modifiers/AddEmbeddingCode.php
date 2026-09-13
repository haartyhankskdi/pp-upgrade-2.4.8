<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Ui\DataProvider\Modifiers;

use Amasty\Customform\Api\Data\FormInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

class AddEmbeddingCode implements ModifierInterface
{
    public function modifyMeta(array $meta): array
    {
        return $meta;
    }

    public function modifyData(array $data): array
    {
        $data['cms'] = sprintf(
            '{{widget type="Amasty\Customform\Block\Init" template="Amasty_Customform::init.phtml" form_id="%s"}}',
            $data[FormInterface::FORM_ID]
        );

        $data['template'] = sprintf(
            '<?= $this->helper("Amasty\Customform\Helper\Data")->renderForm(%s) ?>',
            (int) $data[FormInterface::FORM_ID]
        );

        $data['layout'] = sprintf(
            '<referenceContainer name="content">
            <block class="Amasty\Customform\Block\Init" name="amasty.customform.init.%s">
                <arguments>
                    <argument name="form_id" xsi:type="string">%s</argument>
                </arguments>
            </block>
            </referenceContainer>',
            $data[FormInterface::FORM_ID],
            $data[FormInterface::FORM_ID]
        );

        return $data;
    }
}
