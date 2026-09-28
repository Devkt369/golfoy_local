<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magestore\BranchRequest\Ui\DataProvider\Product\Form\Modifier;

use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\InventoryCatalog\Model\GetDefaultSourceItemBySku;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\Ui\Component\Form;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * Class BranchRequest Racket
 */
class Racket extends AbstractModifier
{
    const RACKET = 'racket';

    const EXCEPT_PRODUCT_TYPES = [
        Configurable::TYPE_CODE,
        Bundle::TYPE_CODE,
        Grouped::TYPE_CODE
    ];

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var ArrayManager
     * @since 101.0.0
     */
    protected $arrayManager;

    /**
     * @var GetDefaultSourceItemBySku
     */
    private $getDefaultSourceItemBySku;
    
    /**
     * Racket constructor.
     *
     * @param \Magento\Catalog\Model\Locator\LocatorInterface $locator
     * @param \Magento\Framework\Stdlib\ArrayManager $arrayManager
     * @param \Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface $isSingleSourceMode
     * @param \Magento\InventoryCatalog\Model\GetDefaultSourceItemBySku $getDefaultSourceItemBySku
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        IsSingleSourceModeInterface $isSingleSourceMode,
        GetDefaultSourceItemBySku $getDefaultSourceItemBySku
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->getDefaultSourceItemBySku = $getDefaultSourceItemBySku;
    }

    /**
     * Modify data
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $data = $this->getRacketData($data);
        return $data;
    }

    /**
     * Get Racket Data
     *
     * @param array $data
     * @return array
     */
    public function getRacketData(array $data)
    {
        $model = $this->locator->getProduct();
        $modelId = $model->getId();
        if ($this->isSingleSourceMode->execute() === true) {
            $sku = $model->getSku();
            if ($sku) {
                $defaultSourceItem = $this->getDefaultSourceItemBySku->execute($sku);
                $racket = ($defaultSourceItem && $defaultSourceItem->getData('racket')) ?
                    $defaultSourceItem->getData('racket') : '';
                $data[$modelId]['product']['racket'] = $racket;
            }
        }
        
        return $data;
    }

    /**
     * Modify meta
     *
     * @param array $meta
     * @return array|null
     */
    public function modifyMeta(array $meta)
    {
        $meta = $this->getRacketField($meta);

        return $meta;
    }

    /**
     * Add racket field to product form
     *
     * @param array $meta
     * @return array|null
     */
    public function getRacketField(array $meta)
    {
        $product = $this->locator->getProduct();
        if (in_array($product->getTypeId(), self::EXCEPT_PRODUCT_TYPES)) {
            return $meta;
        }
        if ($this->isSingleSourceMode->execute() !== true) {
            return $meta;
        }
        $fieldCode = self::RACKET;
        $meta = array_replace_recursive(
            $meta,
            [
                'product-details' => [
                    'children' => [
                        'container_racket' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'formElement' => 'container',
                                        'componentType' => 'container',
                                        'breakLine' => false,
                                        'label' => __('Racket'),
                                        'required' => false,
                                        'sortOrder' => 50
                                    ],
                                ],
                            ],
                            'children' => [
                                $fieldCode => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'dataType' => Form\Element\DataType\Text::NAME,
                                                'formElement' => Form\Element\Input::NAME,
                                                'componentType' => Form\Field::NAME,
                                                'visible' => 1,
                                                'required' => false,
                                                'notice' => '',
                                                'default' => '',
                                                'label' => __('Racket'),
                                                'code' => $fieldCode,
                                                'source' => self::DEFAULT_GENERAL_PANEL,
                                                'fit' => true,
                                                'validation' => [
                                                ]
                                            ],
                                        ],
                                    ],
                                ],
                            ]
                        ],
                    ]
                ],
            ]
        );

        return $meta;
    }
}
