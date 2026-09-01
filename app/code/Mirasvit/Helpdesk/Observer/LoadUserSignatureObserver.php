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


namespace Mirasvit\Helpdesk\Observer;

use Magento\Framework\Event\ObserverInterface;

class LoadUserSignatureObserver implements ObserverInterface
{
    /**
     * @var \Mirasvit\Helpdesk\Model\UserFactory
     */
    private $userFactory;

    /**
     * LoadUserSignatureObserver constructor.
     * @param \Mirasvit\Helpdesk\Model\UserFactory $userFactory
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\UserFactory $userFactory
    ) {
        $this->userFactory = $userFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\User\Model\User $user */
        $user = $observer->getObject();
        if (!$user->getId()) {
            return;
        }
        $helpdeskUser = $this->userFactory->create();
        $resource = $helpdeskUser->getResource();
        $resource->load($helpdeskUser, $user->getId());
        if (!$helpdeskUser || !$helpdeskUser->getId()) {
            return;
        }
        $signature = $helpdeskUser->getSignature();
        if ($signature == strip_tags($signature)) {
            $signature = nl2br($signature);
        }
        $user->setSignature($signature);
    }
}
