<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Block\Adminhtml\Grid\Column\Render;

/**
 * ArrayToString column renderer
 */
class ArrayToString extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * Render category grid column
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($data = $row->getData($this->getColumn()->getIndex())) {
            $ids = [];
            foreach ($data as $id) {
                if (is_object($id)) {
                    $ids[] = $id->getId();
                } else {
                    $ids[] = $id;
                }
            }

            return implode(',', $ids);
        }
        return null;
    }
}
