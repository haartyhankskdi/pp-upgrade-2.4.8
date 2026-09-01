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

class Mirasvit_Ddeboer_Imap_Exception_MessageDeleteException extends Mirasvit_Ddeboer_Imap_Exception_Exception
{
    /**
     * Mirasvit_Ddeboer_Imap_Exception_MessageDeleteException constructor.
     * @param mixed $messageNumber
     */
    public function __construct($messageNumber)
    {
        parent::__construct(sprintf('Message %s cannot be deleted', $messageNumber));
    }
}
