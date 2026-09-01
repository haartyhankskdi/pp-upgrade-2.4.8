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

class FineDiffInsertOp extends \Bss\AdminActionLog\Convert\FineDiffOp
{
    /**
     * @var string $text
     */
    private $text;
    /**
     * FineDiffInsertOp constructor.
     * @param $text
     */
    public function __construct($text)
    {
        $this->text = $text;
    }

    /**
     * Get From Len
     *
     * @return int
     */
    public function getFromLen()
    {
        return 0;
    }

    /**
     * GetToLen
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
        $toLen = strlen($this->text);
        if ($toLen === 1) {
            return "i:{$this->text}";
        }
        return "i{$toLen}:{$this->text}";
    }
}
