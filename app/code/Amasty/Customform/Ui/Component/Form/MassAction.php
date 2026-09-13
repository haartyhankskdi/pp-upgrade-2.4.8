<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Ui\Component\Form;

use Amasty\Customform\Api\Data\FormInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class MassAction extends \Magento\Ui\Component\MassAction
{
    public const ADMIN_ACTION_RESOURCE = [
        'delete' => FormInterface::ADMIN_RESOURCE_DELETE
    ];

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    public function __construct(
        AuthorizationInterface $authorization,
        ContextInterface $context,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->authorization = $authorization;
    }

    public function prepare()
    {
        parent::prepare();
        $config = $this->getConfiguration();
        $allowedActions = [];
        foreach ($config['actions'] as $action) {
            if ($this->isAllowedAction($action['type'])) {
                $allowedActions[] = $action;
            }
        }
        $config['actions'] = $allowedActions;
        $this->setData('config', $config);
    }

    private function isAllowedAction(string $action): bool
    {
        return !isset(self::ADMIN_ACTION_RESOURCE[$action])
            || $this->authorization->isAllowed(self::ADMIN_ACTION_RESOURCE[$action]);
    }
}
