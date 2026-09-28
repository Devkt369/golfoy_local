<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Sales Rules Wizard for Magento 2 (System)
 */

namespace Amasty\SalesRuleWizard\Model\OptionsProvider\FreeGift;

use Amasty\SalesRuleWizard\Model\AllowedProductTypes;
use Magento\Framework\App\ObjectManager;

class ProductType extends \Magento\Catalog\Model\Product\Type implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get product type labels array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $options = parent::getOptionArray();
        //use OM to avoid loading dependencies of parent class
        $allowedProductTypes = ObjectManager::getInstance()->get(AllowedProductTypes::class);
        $allowedTypes = $allowedProductTypes->get();
        foreach ($options as $typeId => $type) {
            if (!in_array($typeId, $allowedTypes, true)) {
                unset($options[$typeId]);
            }
        }

        return $options;
    }
}
