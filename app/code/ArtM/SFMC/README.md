# Mage2 Module ArtM SFMC

    ``artm/module-sfmc``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
sfmc content

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/ArtM`
 - Enable the module by running `php bin/magento module:enable ArtM_SFMC`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require artm/module-sfmc`
 - enable the module by running `php bin/magento module:enable ArtM_SFMC`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Controller
	- adminhtml > sfmc/index/index

 - Controller
	- adminhtml > sfmc/cron/customer

 - Cronjob
	- artm_sfmc_customer

 - Observer
	- adminhtml_customer_save_after > ArtM\SFMC\Observer\Adminhtml\CustomerSaveAfter


## Attributes

 - Customer - Syncedsfmc (sfmcsync)

