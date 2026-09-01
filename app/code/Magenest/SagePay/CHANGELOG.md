# Change Log
All notable changes to SagePay Payment Gateway and Subscription extension will be documented in this file.
This extension adheres to [Magenest](http://magenest.com/).

SagePay compatible with 
```
Magento Commerce 2.2.x, 2.3.x, 2.4.x
Magento OpenSource 2.2.x, 2.3.x, 2.4.x
```
## [2.2.0] - 2021-11-26
- Add Feature 3DS2 for PI Integration
- Fix bug partial refund
- Fix bug Subscription
- Fix coding standard

## [2.1.0] - 2021-08-02
- Fix bug with subscriptions when the product stock is not managed
- Fix bug with multiple stores
- Add toggle for logging feature

## [2.0.9] - 2021-06-24
- Fix bug mini cart Sagepay Form, Sagepay PI
- Fix missing vendor tx code
- Fix save card error

## [2.0.8] - 2021-04-29
- Fix a bug when displaying transactions on admin interface
- Fix a bug with color depth on browsers
- Support multiple currency
- Add gateway currency in the backend config

## [2.0.7] - 2020-12-08
- Fix lost session after redirecting to payment page
- Fix bugs multiple websites
- Fix discount in repeat transactions

## [2.0.6] - 2020-08-14
- Fix direct method (cannot get order after 3d)
- Fix send order confirm email

## [2.0.5] - 2020-07-08
- Change module name to Opayo
- Fix bug save card

## [2.0.4] - 2020-06-26
- Fix bug check API button(Sage PI)
- Fix bug show Sage Server form in other Sage Gateway
- Fix type column customer_id in 'magenest_sagepay_transaction' table
- Fix name interface API magento
- Add CSP whitelist

## [2.0.3] - 2020-03-17
- Fix bug payment and refund MOTO with multiple stores
- Fix bug authorize only
- Add Sagepay Direct to multishipping

## [2.0.2] - 2020-02-12
- Fix bugs: payment with American Express in sagepay PI, duplicate orders
in sagepay Form
- Fix small bugs

## [2.0.1] - 2019-10-03
- Fix bugs: payment with Paypal, payment with PI
- Fix send ajax in checkout page

## [2.0.0] - 2019-09-09
-   Rework SagePay library integration
-   Add: 3DS2
-   Fixbug: transaction grid
-   Fix: send API in checkout

## [1.8.2] - 2019-04-06
-   Add: Support multiple language
-   Fix: Small bug on Magento version 2.1
-   Fix: Argument error on Magento version 2.1
-   Fix: Entity not existed in transaction grid when order data error
-   Fix: Restore order only when status response OK
-   Fix: Remove some character not Allow in Sagepay Basket

## [1.8.1] - 2019-02-21
-   Add: Loading screen when place order with sagepay drop-in
-   Fix: Payment sometime display error exception in adminhtml
-   Fix: Handle Special character in product name and SKU
-   Fix: Error when set api key working with multiple website

## [1.8.0] - 2019-01-18
-   Add: Sagepay transaction grid: admin can view all sagepay transaction.
-   Add: View sagepay transaction info, admin can view all transaction status with additional information
-   Add: Restore order if order cannot create in magento
-   Add: Billing aggrement option for sagepay paypal
-   Fixbug: Order cancel unexcepted  
-   Fixbug: processing sagepay form integration
-   Fixbug: Placeorder sagepay in adminhtml
-   Improve performance and stability

## [1.7.1] - 2018-12-03
-   SagePay now compatible with Magento 2.3
-   Fix: Exception when create product
-   Fix: Processing subscription product
-   Fix: PayPal integration
-   Fix: Get merchant session key PI integration.

## [1.7.0] - 2018-11-21
-   Add: Sage Pay Direct
-   Add: Sage Pay Server
-   Add: Sage Pay Paypal
-   Add: Sage drop in backend adminhtml
-   Add: Choose sage dropin display mode

## [1.6.8] - 2018-10-29
-   Add: validate quote address before place order
-   Add: Show error message when credit card is deny
-   Add: sagepay dropin in backend html
-   Fix bug duplicate response sagepay
-   Fix bug order sometime duplicated
-   Fix bug order confirmation email was not send
-   Fix bug Email = null when customer redirect back
-   Fix bug Order status cancel unexpected
-   Fix bug Sagepay direct display weird adminhtml
-   Fix crypt function error in php 7.1

## [1.6.7] - 2018-09-18
-   Fix bug display message response from sagepay
-   Fix bug expire date error (sagepay PI)
-   Fix crypt function deprecated

## [1.6.6] - 2018-06-24
-   Fix term of condition error when using sagepay dropin interface
-   Fix sagepay form fail response
-   Add validate sagepay script load error.

## [1.6.5] - 2018-06-24
-   Add Sagepay library
-   Add Sagepay form Integration
-   Add Sagepay Paypal payment
-   Add SagePay Refund API

## [1.6.0] - 2018-05-24
-   Fix javascript library load error
-   Fix sage display error with onestepcheckout
-   Move drop-in interface to sagepay payment

## [1.5.0] - 2018-01-26
Sagepay compatible with 
```
Magento Commerce 2.1.x, 2.2.x, 
Magento OpenSource 2.1.x, 2.2.x
```
-   Added ReferrerId
-   Improve security
-   Fix sagepay dropin error when load checkout page

## [1.4.0] - 2018-01-03
Sagepay compatible with 
```
Magento Commerce 2.1.x, 2.2.x, 
Magento OpenSource 2.1.x, 2.2.x
```
### Added
-   Improve payment security
-   Add validate payment response
-   Sage own form: PCI-DSS compliant using the SAQ-A-EP self assessment questionnaire
-   Sage drop-in: PCI-DSS compliant using the SAQ-A self assessment questionnaire
-   Add alert box when check API key
### Fixed
-   Fix bug gift aid is not selected
-   Submit order bug freeze
-   Fix bug response duplicated
-   Fix Sage Js loaded two time(speed up checkout page)
### Remove
-   Remove option debug log. The debug file now stored in `{magento}/var/log/sagepay/debug.log`

## [1.3.5] - 2017-12-05
### Added
-   Improve security
-   Add check Api in config backend
-   Add support information in config backend
-   Change backend config layout
### Fixed
-   Fix bug refund order error when vendor prefix code input very long
-   Fix bug require js loading

## [1.3.0] - 2017-11-13
### Added
-   Add Sage Moto Payment Service
-   Add total amount order check.
### Fixed
-   Fix bug 3d secure response with error status

## [1.2.0] - 2017-10-14
### Update
-   Sagepay compatible with Magento Commerce 2.1.x, 2.2.x, Magento OpenSource 2.1.x, 2.2.x
-   Fix bug in edit product page
### Added
-   Notify user when merchant key error, payment error return from bank
-   Add browser output console log.
-   Add some helpful comment in backend config.
-   Add capture notification for customer when cron job running.
### Fixed
-   Fix checkout layout broken in mobile screen.
-   Fix default config sagepay.
-   Fix cron job running everyday.

## [1.1.0] - 2017-08-11
### Big update
-   A lot of bug was fix
-   Compatible with CE, EE: 2.1.x

### Fixed
-   Fix send mail error when checkout with 3d secure
-   Partial refunds after create invoice
-   Fix bug restore cart when 3d secure fail
-   Fix bug order state pending when 3d secure response fail
-   Fix bug conflict javascript with some one step checkout
-   Fix bug different billing address and shipping address
-   Fix bug currency multiply error with some non-decimal currency type
-   Fix bug save card with some type of credit card
-   Fix bug show logo error (404 blank image)
-   Fix bug post code and state missing with some country

### Added
-   Gift Aid: end-user now have a checkbox to donate the taxes.
-   Sort order: User can input the number to sort the payment method.
-   Payment instructions: Admin can input html payment instructions 
-   Add some javascript response to website
-   Add ignore address check(in test mode)

### Changed
-   Debug logger now clean and easy to read.
-   Change all the sage-pay javascript library to pi-live.sagepay.com 
-   Sagepay drop-in method now using custom i-frame form

## [1.0.3] - 2017-07-10
### Fixed
-   Sagepay payment error
-   Subscription
-   Sagepay create product error
-   Some bug when capture, refund
-   Checkout loader
### Added
-   Save card token
-   Save card and usable

## [1.0.2] - 2016-12-23
### Added
1. Multiple subscription profiles

## [1.0.1] - 2016-10-20
### Added
1. Fix sagepay.js error for test/live integration

## [1.0.0] - 2016-08-13
### Added
1. Allow customers to checkout using credit card payment
2. Allow admins to integrate their SagePay account to the Magento store
    - Enable or disable the gateway
    - Integrate using vendor credential
    - Specify allowed credit card types and countries
    - Specify minimum and maximum order amount
3. Add a layer of security with 3D checkout
4. Support multiple stores
5. Easily check transaction history
6. Admins can create subscription plans for each products, each includes
    - Subscription frequency
    - Total amount of cycles for a profile (0 for ongoing subscription)
7. Customers can subscribe to an available plan on a specific product. A profile will then be created.
8. Admins can easily manage profiles or disable them.
9. Customers can also manage their profiles and disable them as well.
