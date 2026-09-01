# Mage2 Module Nilesh OrderStatus

    ``nilesh/module-orderstatus``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities


## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Nilesh`
 - Enable the module by running `php bin/magento module:enable Nilesh_OrderStatus`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require nilesh/module-orderstatus`
 - enable the module by running `php bin/magento module:enable Nilesh_OrderStatus`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

 - Enable (sales_email/custom_approve_status/enable)

 - Email Sender (sales_email/custom_approve_status/identity)

 - Select Email Template (sales_email/custom_approve_status/approve_template)

 - Send Order Email Copy To (sales_email/custom_approve_status/copy_to)

 - Enable (sales_email/custom_disapprove_status/enable)

 - Email Sender (sales_email/custom_disapprove_status/identity)

 - Select Email Template (sales_email/custom_disapprove_status/disapprove_template)

 - Send Order Email Copy To (sales_email/custom_disapprove_status/copy_to)


## Specifications

 - Observer
	- sales_order_save_after > Nilesh\OrderStatus\Observer\Backend\Sales\OrderSaveAfter


## Attributes



