<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Block\Adminhtml\Grid\Column;

/**
 * Admin blog grid column
 */
class Text extends \Magento\Backend\Block\Widget\Grid\Column
{
    /**
     * Constructor
     *
     * @return void
     */
    public function _construct(): void
    {
        parent::_construct();
        $this->_rendererTypes['text'] = \Magefan\BlogImport\Block\Adminhtml\Grid\Column\Render\Text::class;
    }
}
