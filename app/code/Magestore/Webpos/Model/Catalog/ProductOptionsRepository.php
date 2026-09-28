<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Webpos\Model\Catalog;

use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\ObjectManagerInterface;
use Magestore\Webpos\Api\Catalog\ProductOptionsRepositoryInterface;
use Magestore\Webpos\Api\Data\Catalog\Option\Config\Attribute\Option\ProductInterface;
use Magestore\Webpos\Api\Data\Catalog\Option\Config\Attribute\Option\ProductInterfaceFactory;
use Magestore\Webpos\Api\Data\Catalog\Option\Config\Attribute\OptionInterface;
use Magestore\Webpos\Api\Data\Catalog\Option\Config\Attribute\OptionInterfaceFactory;
use Magestore\Webpos\Api\Data\Catalog\Option\ConfigOptionsInterface;
use Magestore\Webpos\Api\Data\Catalog\Option\ConfigOptionsInterfaceFactory;
use Magestore\Webpos\Api\Data\Catalog\Option\ProductOptionsInterface;

/**
 * Webpos - ProductOptionsRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ProductOptionsRepository implements ProductOptionsRepositoryInterface
{
    /**
     * @var ProductOptionsInterface
     */
    protected $productOptions;
    /**
     * @var ConfigOptionsInterfaceFactory
     */
    protected $configOptionsFactory;
    /**
     * @var OptionInterfaceFactory
     */
    protected $optionFactory;
    /**
     * @var ProductInterfaceFactory
     */
    protected $productInterfaceFactory;
    /**
     * @var ProductFactory
     */
    protected $productFactory;
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $currentProduct;
    protected $currentProductTypeInstance;

    /**
     * ProductOptionsRepository constructor.
     * @param ProductOptionsInterface $productOptions
     * @param ConfigOptionsInterfaceFactory $configOptionsFactory
     * @param OptionInterfaceFactory $optionFactory
     * @param ProductInterfaceFactory $productInterfaceFactory
     * @param ProductFactory $productFactory
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ProductOptionsInterface $productOptions,
        ConfigOptionsInterfaceFactory $configOptionsFactory,
        OptionInterfaceFactory $optionFactory,
        ProductInterfaceFactory $productInterfaceFactory,
        ProductFactory $productFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->productOptions = $productOptions;
        $this->configOptionsFactory = $configOptionsFactory;
        $this->optionFactory = $optionFactory;
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->productFactory = $productFactory;
        $this->objectManager = $objectManager;
    }

    /**
     * @inheritdoc
     */
    public function getProductOptions($product_id)
    {
        $product = $this->productFactory->create()->load($product_id);

        $productOptions = $this->productOptions;

        if (!$product->getId()) {
            return $productOptions;
        }

        $this->currentProduct = $product;

        $this->addConfigOptions($product, $productOptions);

        return $productOptions;
    }

    /**
     * Add Config Options
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param ProductOptionsInterface $productOptions
     */
    public function addConfigOptions($product, &$productOptions)
    {
        $configOptions = [];

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            /** @var Configurable $productTypeInstance */
            $productTypeInstance = $this->_getProductTypeInstance($product);
            $productAttributeOptions = $productTypeInstance->getConfigurableAttributesAsArray($product);
            $this->currentProductTypeInstance = $productTypeInstance;

            foreach ($productAttributeOptions as $productAttributeOption) {
                $productOptions->setIsOptions(1);
                /** @var ConfigOptionsInterface $attribute */
                $attribute = $this->configOptionsFactory->create();
                $attribute->setId($productAttributeOption['attribute_id']);
                $attribute->setCode($productAttributeOption['attribute_code']);
                $attribute->setLabel($productAttributeOption['label']);
                $attribute->setPosition($productAttributeOption['position']);

                $this->addOptionToAttribute($attribute, $productAttributeOption);

                $configOptions[] = $attribute;
            }

        }

        $productOptions->setConfigOption($configOptions);
    }

    /**
     * Add Option To Attribute
     *
     * @param ConfigOptionsInterface $attribute
     * @param array $productAttributeOption
     */
    public function addOptionToAttribute(&$attribute, $productAttributeOption)
    {
        $options = [];
        foreach ($productAttributeOption['values'] as $option) {
            /** @var OptionInterface $optionObj */
            $optionObj = $this->optionFactory->create();
            $optionObj->setId($option['value_index']);
            $optionObj->setLabel($option['label']);

            $this->addProductToOption($optionObj, $attribute);

            $options[] = $optionObj;
        }
        $attribute->setOptions($options);
    }

    /**
     * Add Product To Option
     *
     * @param OptionInterface $optionObj
     * @param ConfigOptionsInterface $attribute
     */
    public function addProductToOption(&$optionObj, $attribute)
    {
        $products = [];

        $usedProducts = $this->currentProductTypeInstance->getUsedProducts($this->currentProduct);
        foreach ($usedProducts as $product) {
            if ($product->getData($attribute->getCode()) == $optionObj->getId()) {
                /** @var ProductInterface $pr */
                $pr = $this->productInterfaceFactory->create();
                $pr->setId($product->getId());
                $pr->setPrice(round((float)$product->getPrice(), 4));
                $pr->setBasePrice(round((float)$product->getBasePrice(), 4));

                $products[] = $pr;
            }
        }
        $optionObj->setProducts($products);
    }

    /**
     * Get product type instance
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed
     */
    public function _getProductTypeInstance($product = null)
    {
        $type = '';
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $type = Configurable::class;
        }
        return $this->objectManager->get($type);
    }
}
