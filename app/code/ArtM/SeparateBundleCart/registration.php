<?php
/**
 * ArtM SeparateBundleCart Module Registration
 *
 * When "add_products_separately" attribute = Yes on bundle product,
 * adds selected simple products to cart individually instead of as bundle.
 *
 * @author ArtM
 * @package ArtM_SeparateBundleCart
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'ArtM_SeparateBundleCart',
    __DIR__
);