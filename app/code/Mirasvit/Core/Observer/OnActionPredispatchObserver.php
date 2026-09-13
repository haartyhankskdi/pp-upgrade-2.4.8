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
 * @package   mirasvit/module-core
 * @version   1.7.20
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Core\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Mirasvit\Core\Model\NotificationFeedFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Mirasvit\Core\Model\LicenseFactory;

class OnActionPredispatchObserver implements ObserverInterface
{
    /**
     * @var bool
     */
    public static $notified = false;

    /**
     * @var NotificationFeedFactory
     */
    private $feedFactory;

    /**
     * @var ManagerInterface
     */
    private $manager;

    /**
     * @var LicenseFactory
     */
    private $licenseFactory;

    private $moduleManager;

    /**
     * OnActionPredispatchObserver constructor.
     *
     * @param NotificationFeedFactory $feedFactory
     * @param ManagerInterface        $manager
     * @param LicenseFactory          $licenseFactory
     */
    public function __construct(
        NotificationFeedFactory $feedFactory,
        ManagerInterface        $manager,
        LicenseFactory          $licenseFactory,
        ModuleManager           $moduleManager
    ) {
        $this->feedFactory    = $feedFactory;
        $this->manager        = $manager;
        $this->licenseFactory = $licenseFactory;
        $this->moduleManager  = $moduleManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(EventObserver $observer)
    {
        $action = $observer->getData('controller_action');

        if (is_object($action) && substr(get_class($action), 0, 9) == 'Mirasvit\\') {
            $module = $this->getModuleName(get_class($action));

            // Only enforce for a module actually installed and enabled on this store — the license
            // server can report an issue naming a product that isn't present here (mis-provisioned
            // records), and we must not act on that phantom.
            if ($module && $this->moduleManager->isEnabled($module)) {
                $status = $this->licenseFactory->create()->getStatus(get_class($action));

                if ($status !== true) {
                    if (!self::$notified) {
                        $this->manager->addErrorMessage($status);
                        self::$notified = true;
                    }
                    // Non-blocking: surface a self-diagnosable notice, but never hard-block the
                    // admin (previously setRouteName('no_route'), which locked merchants out of
                    // the backend over an undiagnosable license message).
                }
            }
        }

        if ($this->moduleManager->isEnabled('Magento_AdminNotification')) {
            $feedModel = $this->feedFactory->create();
            $feedModel->checkUpdate();
        }
    }

    /**
     * Resolve the Mirasvit module name (Mirasvit_Foo) from a controller action class name.
     *
     * @param string $className
     *
     * @return string empty string when the class has no module segment
     */
    private function getModuleName($className)
    {
        $parts = explode('\\', $className);

        return isset($parts[1]) ? 'Mirasvit_' . $parts[1] : '';
    }
}
