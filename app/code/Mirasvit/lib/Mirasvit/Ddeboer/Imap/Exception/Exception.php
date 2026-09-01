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
 * @package   mirasvit/module-helpdesk
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */


// @codingStandardsIgnoreFile
// namespace Mirasvit_Ddeboer\Imap\Exception;

class Mirasvit_Ddeboer_Imap_Exception_Exception extends RuntimeException
{
    /**
     * @var array|false
     */
    protected $errors = array();

    /**
     * Mirasvit_Ddeboer_Imap_Exception_Exception constructor.
     * @param mixed $message
     * @param null $code
     * @param null $previous
     */
    public function __construct($message, $code = null, $previous = null)
    {
        parent::__construct($message, $code);
        $this->errors = imap_errors();
    }

    /**
     * Get IMAP errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
