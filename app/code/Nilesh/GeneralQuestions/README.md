# Mage2 Module Nilesh GeneralQuestions

    ``nilesh/module-generalquestions``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Free to use by every one

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Nilesh`
 - Enable the module by running `php bin/magento module:enable Nilesh_GeneralQuestions`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require nilesh/module-generalquestions`
 - enable the module by running `php bin/magento module:enable Nilesh_GeneralQuestions`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Controller
	- frontend > customer/general/question

 - Observer
	- customer_register_success > Nilesh\GeneralQuestions\Observer\Frontend\Customer\RegisterSuccess

 - Model
	- GeneralQuestions


## Attributes

 - Customer - general_question (general_question)

