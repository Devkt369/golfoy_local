<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Sales Rules Wizard for Magento 2 (System)
 */

namespace Amasty\SalesRuleWizard\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ObjectManager;

class FreeGiftProductDataProvider extends \Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider
{
    /**
     * ProductDataProvider constructor.
     *
     * @param string            $name
     * @param string            $primaryFieldName
     * @param string            $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array             $addFieldStrategies
     * @param array             $addFilterStrategies
     * @param array             $meta
     * @param array             $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        $addFieldStrategies = [],
        $addFilterStrategies = [],
        array $meta = [],
        array $data = [],
        ?AllowedProductTypes $allowedProductTypes = null
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $collectionFactory,
            $addFieldStrategies,
            $addFilterStrategies,
            $meta,
            $data
        );
        if (!$allowedProductTypes) {
            $allowedProductTypes = ObjectManager::getInstance()->get(AllowedProductTypes::class);
        }
        $this->collection->addFieldToFilter(
            'type_id',
            [
                'in' => $allowedProductTypes->get()
            ]
        );

        $this->collection->addAttributeToSelect(['status', 'thumbnail', 'name', 'price'], 'left');
    }
}
