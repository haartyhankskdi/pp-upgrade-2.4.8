<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AdminActionLog
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AdminActionLog\Convert;

class FineDiffReplaceOp extends \Bss\AdminActionLog\Convert\FineDiffOp
{
    /**
     * @var int $fromLen
     */
    private $fromLen;
    /**
     * @var string $text
     */
    private $text;
    /**
     * FineDiffReplaceOp constructor.
     * @param $fromLen
     * @param $text
     */
    public function __construct($fromLen, $text)
    {
        $this->fromLen = $fromLen;
        $this->text = $text;
    }

    /**
     * Get FromLen
     *
     * @return mixed
     */
    public function getFromLen()
    {
        return $this->fromLen;
    }

    /**
     * Get ToLen
     *
     * @return int
     */
    public function getToLen()
    {
        return strlen($this->text);
    }

    /**
     * Get Text
     *
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Get Opcode
     *
     * @return string
     */
    public function getOpcode()
    {
        if ($this->fromLen === 1) {
            $delOpcode = 'd';
        } else {
            $delOpcode = "d{$this->fromLen}";
        }

        $toLen = strlen($this->text);
        if ($toLen === 1) {
            return "{$delOpcode}i:{$this->text}";
        }

        return "{$delOpcode}i{$toLen}:{$this->text}";
    }
}
