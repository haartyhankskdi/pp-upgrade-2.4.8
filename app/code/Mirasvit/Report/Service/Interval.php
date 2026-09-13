<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-report
 * @version   1.4.74
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Report\Service;

use Mirasvit\Core\Model\Date;
use Mirasvit\Report\Api\Service\IntervalInterface;

class Interval implements IntervalInterface
{
    /**
     * @var Date
     */
    private $from;

    /**
     * @var Date
     */
    private $to;

    public function __construct(Date $from, Date $to)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    /**
     * @return Date
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return Date
     */
    public function getTo()
    {
        return $this->to;
    }
}
