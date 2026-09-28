<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class \Magestore\Webpos\Ui\Component\Listing\Column\DetailError
 *
 * Web POS Detail Error Unconverted Order
 * Methods:
 *  getOptions
 *  toOptionArray
 *
 * @module      Webpos
 * @author      Magestore Developer
 */
class DetailError implements OptionSourceInterface
{
    public const SHIPMENT_ERROR = '{"shipment":1}';
    public const INVOICE_ERROR = '{"invoice":1}';
    public const OTHER_ERROR = '';

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return $this->getOptions();
    }

    /**
     * Get detail error labels array for option element
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            ["label" => __('Unsuccessful invoice creation'), "value" => self::INVOICE_ERROR],
            ["label" => __('Unsuccessful shipment creation'), "value" => self::SHIPMENT_ERROR],
            ["label" => __('Cannot be made into Magento’s order'), "value" => self::OTHER_ERROR],
        ];
    }
}
