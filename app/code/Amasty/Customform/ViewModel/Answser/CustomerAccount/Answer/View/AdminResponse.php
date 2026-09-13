<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Answser\CustomerAccount\Answer\View;

use Amasty\Customform\Model\Config\Source\Status;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class AdminResponse implements ArgumentInterface
{
    /**
     * @var CurrentAnswerProvider
     */
    private $currentAnswerProvider;

    /**
     * @var Escaper
     */
    private $escaper;

    public function __construct(
        CurrentAnswerProvider $currentAnswerProvider,
        Escaper $escaper
    ) {
        $this->currentAnswerProvider = $currentAnswerProvider;
        $this->escaper = $escaper;
    }

    public function getAdminResponseData(): array
    {
        $model = $this->currentAnswerProvider->getCurrentResponse();
        $responseStatus = $model->getAdminResponseStatus();

        if ($responseStatus == Status::ANSWERED) {
            $result = [
                [
                    'label' => __('Response Status'),
                    'value' => $this->escaper->escapeHtml(__('Sent')),
                ],
                [
                    'label' => __('Response'),
                    'value' => $this->escaper->escapeHtml($model->getResponseMessage()),
                ],
            ];
        } else {
            $result = [
                [
                    'label' => __('Response Status'),
                    'value' => $this->escaper->escapeHtml(__('Pending')),
                ]
            ];
        }

        return $result;
    }
}
