<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Block;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Widget\Block\BlockInterface;

/**
 * Wrapper for \Amasty\Customform\Block\Form .
 * Don't change class name/namespace for support compatibility for customers with already created widgets.
 */
class Init extends Template implements BlockInterface, IdentityInterface
{
    /**
     * @var Form|null
     */
    private $formBlock;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    public function __construct(
        ModuleManager $moduleManager,
        BlockFactory $blockFactory,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleManager = $moduleManager;
        $this->blockFactory = $blockFactory;
    }

    /**
     * @return string
     */
    public function _toHtml(): string
    {
        $html = '';
        if ($formBlock = $this->getFormBlock()) {
            $html = $formBlock->toHtml();
        }

        return $html;
    }

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        $identities = [];
        if ($formBlock = $this->getFormBlock()) {
            $identities = $formBlock->getIdentities();
        }

        return $identities;
    }

    /**
     * Check if module enabled and return original Form block.
     *
     * @return Form|null
     */
    private function getFormBlock(): ?Form
    {
        if ($this->formBlock === null && $this->moduleManager->isEnabled('Amasty_Customform')) {
            $this->formBlock = $this->blockFactory->createBlock(Form::class, ['data' => $this->getData()]);
            if ($this->getTemplate()) {
                $this->formBlock->setTemplate($this->getTemplate());
            }
        }

        return $this->formBlock;
    }

    public function _resetState(): void
    {
        $this->formBlock = null;
    }
}
