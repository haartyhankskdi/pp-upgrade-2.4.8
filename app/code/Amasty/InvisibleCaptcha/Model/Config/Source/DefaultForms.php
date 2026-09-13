<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Google Invisible reCaptcha for Magento 2
 */

namespace Amasty\InvisibleCaptcha\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DefaultForms implements OptionSourceInterface
{
    public const CUSTOMER_CREATE = 'customer/account/createpost';
    public const CUSTOMER_LOGIN = 'customer/account/loginPost';
    public const CUSTOMER_FORGOTPASSWORD = 'customer/account/forgotpasswordpost';
    public const CUSTOMER_RESETPASSWORD = 'customer/account/resetpasswordpost';
    public const NEWSLETTER_SUBSCRIBE = 'newsletter/subscriber/new';
    public const PRODUCT_REVIEW = 'review/product/post';
    public const CONTACT_US = 'contact/index/post';
    public const CHECKOUT_PAYMENTS = 'checkout_payment_captcha';
    public const CUSTOMER_EDIT_ACCOUNT_INFORMATION = 'customer/account/editPost';

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::CUSTOMER_CREATE,
                'label' => __('Customer Create Account')
            ],
            [
                'value' => self::CUSTOMER_LOGIN,
                'label' => __('Customer Login')
            ],
            [
                'value' => self::NEWSLETTER_SUBSCRIBE,
                'label' => __('News Letter Subscription')
            ],
            [
                'value' => self::CONTACT_US,
                'label' => __('Contact Us')
            ],
            [
                'value' => self::CUSTOMER_FORGOTPASSWORD,
                'label' => __('Customer Forgot Password')
            ],
            [
                'value' => self::PRODUCT_REVIEW,
                'label' => __('Product Review')
            ],
            [
                'value' => self::CUSTOMER_RESETPASSWORD,
                'label' => __('Change Password')
            ],
            [
                'value' => self::CHECKOUT_PAYMENTS,
                'label' => __('Checkout Payments')
            ],
            [
                'value' => self::CUSTOMER_EDIT_ACCOUNT_INFORMATION,
                'label' => __('Customer Edit Account Information')
            ],
        ];
    }
}
