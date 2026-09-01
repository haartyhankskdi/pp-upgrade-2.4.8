<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Filters;

use Magento\Framework\App\RequestInterface;

class RequestFiltersProvider
{
    public const FILTERS_KEY = 'filters';
    public const REPORTS_KEY = 'amreports';

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function execute(): array
    {
        $params = $this->request->getParams();
        $filers = $this->request->getParam(self::FILTERS_KEY, []) ?: [];
        $reports = $this->request->getParam(self::REPORTS_KEY, []) ?: [];
        $params = array_merge($params, $filers, $reports);

        return $params;
    }
}
