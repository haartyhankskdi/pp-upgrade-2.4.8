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



namespace Mirasvit\Helpdesk\Model\Provider\Failure\RedirectUrl;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

// for some reason Magento not always loads RedirectUrlProviderInterface , so we use autoload
if (class_exists('\MSP\ReCaptcha\Model\Provider\Failure\RedirectUrl\SimpleUrlProvider', false)) {
    interface MspRedirectUrlProviderInterface
        extends \MSP\ReCaptcha\Model\Provider\Failure\RedirectUrlProviderInterface {}
} elseif (class_exists('\MSP\ReCaptcha\Model\Provider\Failure\RedirectUrl\SimpleUrlProvider', true)) {
    interface MspRedirectUrlProviderInterface
        extends \MSP\ReCaptcha\Model\Provider\Failure\RedirectUrlProviderInterface {}
} else {
    interface MspRedirectUrlProviderInterface {}
}

class HelpdeskForms implements MspRedirectUrlProviderInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * HelpdeskForms constructor.
     * @param RequestInterface $request
     * @param UrlInterface $url
     */
    public function __construct(
        RequestInterface $request,
        UrlInterface $url
    ) {
        $this->request = $request;
        $this->url     = $url;
    }

    /**
     * Get redirection URL
     * @return string
     */
    public function execute()
    {
        if ($this->request->getControllerName() == 'satisfaction') {
            $uid = $this->request->getParam('uid');

            return $this->url->getUrl('helpdesk/satisfaction/form', ['uid' => $uid]);
        } elseif ($this->request->getControllerName() == 'contact' || $this->request->getControllerName() == 'form') {
            $url = $this->request->getParam('current_url');

            return $this->url->getUrl($url);
        } else {
            return $this->url->getUrl('contact/index/index');
        }
    }
}