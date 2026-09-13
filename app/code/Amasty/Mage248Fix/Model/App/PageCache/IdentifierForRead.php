<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Mage 2.4.8 Fix
 */

declare(strict_types=1);

namespace Amasty\Mage248Fix\Model\App\PageCache;

use Magento\Framework\App\Http\Context;
use Magento\Framework\App\PageCache\Identifier;
use Magento\Framework\App\PageCache\IdentifierInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\PageCache\Model\App\Request\Http\IdentifierStoreReader;

class IdentifierForRead implements IdentifierInterface
{
    /**
     * @var Http
     */
    private Http $request;

    /**
     * @var Context
     */
    private Context $context;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @var IdentifierStoreReader
     */
    private IdentifierStoreReader $identifierStoreReader;

    /**
     * @var Identifier
     */
    private Identifier $identifier;

    public function __construct(
        Http $request,
        Context $context,
        Json $serializer,
        IdentifierStoreReader $identifierStoreReader,
        Identifier $identifier
    ) {
        $this->request = $request;
        $this->context = $context;
        $this->serializer = $serializer;
        $this->identifierStoreReader = $identifierStoreReader;
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        $pattern = $this->identifier->getMarketingParameterPatterns();
        $replace = array_fill(0, count($pattern), '');
        $data = [
            $this->request->isSecure(),
            preg_replace($pattern, $replace, (string)$this->request->getUriString()),
            $this->request->get(\Magento\Framework\App\Response\Http::COOKIE_VARY_STRING)
                ?: $this->context->getVaryString()
        ];

        $data = $this->identifierStoreReader->getPageTagsWithStoreCacheTags($data);

        return sha1($this->serializer->serialize($data));
    }
}
