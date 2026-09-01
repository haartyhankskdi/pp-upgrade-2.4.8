# Mage2 Module Nilesh ProductCustomOption

    ``nilesh/module-productcustomoption``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
This module is created by Nilesh Dubey

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Nilesh`
 - Enable the module by running `php bin/magento module:enable Nilesh_ProductCustomOption`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require nilesh/module-productcustomoption`
 - enable the module by running `php bin/magento module:enable Nilesh_ProductCustomOption`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

 - custom_option_field_enable (custom_option_section/custom_option_group/custom_option_field_enable)


## Specifications

 - Block
	- prefilled > prefilled.phtml


## Attributes



