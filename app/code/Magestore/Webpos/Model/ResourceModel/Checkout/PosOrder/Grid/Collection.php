<?php

/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 *
 */

namespace Magestore\Webpos\Model\ResourceModel\Checkout\PosOrder\Grid;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magestore\Webpos\Model\Checkout\PosOrder;
use Magestore\Webpos\Ui\Component\Listing\Column\DetailError;

/**
 * Class \Magestore\Webpos\Model\ResourceModel\Denomination\PosOrder\Grid\Collection
 */
class Collection extends SearchResult
{
    const ADDITIONAL_DATA = 'additional_data';
    const WEBPOS_UNCONVERTED_LISTING = 'webpos_unconverted_listing';

    /**
     * @inheritdoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $request = ObjectManager::getInstance()->get(Http::class);
        $namespace = $request->getParam('namespace') ? $request->getParam('namespace') : "";
        if (self::WEBPOS_UNCONVERTED_LISTING === $namespace) {
            if (self::ADDITIONAL_DATA === $field) {
                $value = $condition;
                if (is_array($condition)) {
                    $value = $condition['eq'];
                }
                if (DetailError::OTHER_ERROR === $value) {
                    $field = 'status';
                    $condition = ['eq' => PosOrder::STATUS_FAILED];
                } else {
                    $condition = ['like' => '%' . $value . '%'];
                }
            }
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
