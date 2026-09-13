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


namespace Mirasvit\Report\Api\Service;

use Mirasvit\Report\Api\Data\EmailInterface;

interface EmailServiceInterface
{
    /**
     * @param EmailInterface $email
     * @return bool
     */
    public function send(EmailInterface $email);

    /**
     * Send email notification by ID.
     *
     * @param int $emailId
     * @param string|null $recipientEmail
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendById(int $emailId, ?string $recipientEmail = null): bool;
}
