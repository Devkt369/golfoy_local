# Mage2 Module ArtM Review

    ``artm/module-review``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Review popup to boost reviews on website

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/ArtM`
 - Enable the module by running `php bin/magento module:enable ArtM_Review`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require artm/module-review`
 - enable the module by running `php bin/magento module:enable ArtM_Review`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

 - module (review/general/module)

 - from_days_default_30 (review/general/from_days_default_30)

 - to_days_default_60 (review/general/to_days_default_60)

 - comments_for_5_star (review/general/comments_for_5_star)

 - comments_for_4_star (review/general/comments_for_4_star)

 - comments_for_3_star (review/general/comments_for_3_star)

 - comments_for_2_star (review/general/comments_for_2_star)

 - comments_for_1_star (review/general/comments_for_1_star)


## Specifications




## Attributes



