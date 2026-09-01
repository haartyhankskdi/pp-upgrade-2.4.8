<?php

declare(strict_types=1);

namespace Amasty\Reports\Model;

use Magento\Backend\Model\Session;
use Amasty\Reports\Model\ResourceModel\Filters\RequestFiltersProvider;

class Store
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var RequestFiltersProvider
     */
    private $requestFiltersProvider;

    public function __construct(
        Session $session,
        RequestFiltersProvider $requestFiltersProvider
    ) {
        $this->session = $session;
        $this->requestFiltersProvider = $requestFiltersProvider;
    }

    /**
     * @return int
     */
    public function getCurrentStoreId(): int
    {
        $params = $this->requestFiltersProvider->execute();
        $storeId = $params[RequestFiltersProvider::REPORTS_KEY]['store'] ?? null;
        if ($storeId === null) {
            $storeId = $this->session->getAmreportsStore();
        }

        return (int) $storeId;
    }

    /**
     * @param int $storeId
     * @return Session
     */
    public function setCurrentStore(int $storeId): Session
    {
        return $this->session->setAmreportsStore($storeId);
    }
}
