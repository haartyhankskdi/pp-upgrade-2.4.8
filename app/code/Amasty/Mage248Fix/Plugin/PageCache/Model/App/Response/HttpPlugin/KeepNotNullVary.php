<?php

declare(strict_types=1);

namespace Amasty\Mage248Fix\Plugin\PageCache\Model\App\Response\HttpPlugin;

use Magento\Framework\App\Http\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\PageCache\Model\App\Response\HttpPlugin;

/**
 * Some modules can change vary string for guests so it will no longer be null.
 * Magento 248 invalidates cache on page where vary string from cookie and context are differ.
 * That resolves into the situation when new guests will always be out of cache
 * because they cookie equals to null and context vary is not.
 * This plugin prevents this situation by setting cookie vary string param equal to context string into request
 */
class KeepNotNullVary
{
    public function __construct(
        private readonly Context $context,
        private readonly HttpRequest $request
    ) {
    }

    public function beforeBeforeSendResponse(HttpPlugin $subject, HttpResponse $subjectParam): void
    {
        $currentVary = $this->context->getVaryString();
        $varyCookie = $this->request->get(HttpResponse::COOKIE_VARY_STRING);
        if ($varyCookie === null && $varyCookie !== $currentVary) {
            $this->request->setParam(HttpResponse::COOKIE_VARY_STRING, $currentVary);
        }
    }
}
