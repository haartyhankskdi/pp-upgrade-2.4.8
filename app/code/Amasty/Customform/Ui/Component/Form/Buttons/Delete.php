<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Ui\Component\Form\Buttons;

use Amasty\Customform\Api\Data\FormInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Delete implements ButtonProviderInterface
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
        $buttonData = [];

        if ($this->getFormId()) {
            $buttonData = [
                'label' => __('Delete'),
                'class' => 'delete',
                'aclResource' => FormInterface::ADMIN_RESOURCE_DELETE,
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this?'
                ) . '\', \'' . $this->getDeleteUrl() . '\', {"data": {}})',
                'sort_order' => 222
            ];
        }

        return $buttonData;
    }

    private function getDeleteUrl(): string
    {
        return $this->urlBuilder->getUrl(
            '*/*/delete',
            [self::ID => $this->getFormId()]
        );
    }

    private function getFormId(): int
    {
        return (int) $this->request->getParam(self::ID);
    }
}
