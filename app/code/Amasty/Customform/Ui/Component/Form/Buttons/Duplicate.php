<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Ui\Component\Form\Buttons;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Duplicate implements ButtonProviderInterface
{
    public const ID = 'form_id';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    public function getButtonData()
    {
        $data = [];

        if ($this->getFormId()) {
            $data = [
                'label' => __('Duplicate'),
                'class' => 'save',
                'on_click' => 'setLocation(\'' . $this->getDuplicateUrl() . '\')',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'UpdateEdit', 'target' => '#edit_form'],
                    ],
                ],
                'sort_order' => 111
            ];
        }

        return $data;
    }

    private function getDuplicateUrl(): string
    {
        return $this->urlBuilder->getUrl(
            '*/*/duplicate',
            ['_current' => true, 'back' => null, Delete::ID => $this->getFormId()]
        );
    }

    private function getFormId(): int
    {
        return (int) $this->request->getParam(self::ID);
    }
}
