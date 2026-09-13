<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer;

use Amasty\Customform\Api\Data\AnswerInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface FinderInterface
{
    /**
     * @return bool
     */
    public function isEmptyResult(): bool;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria): void;

    /**
     * @return AnswerInterface[]
     */
    public function getResult(): iterable;

    /**
     * @return int
     */
    public function getResultsCount(): int;
}
