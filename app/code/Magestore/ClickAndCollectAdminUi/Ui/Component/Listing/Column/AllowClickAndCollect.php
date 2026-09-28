<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\ClickAndCollectAdminUi\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Status
 *
 * Web POS Status Actions
 * Methods:
 *  toOptionArray
 *
 * @module      ClickAndCollectAdminUi
 * @author      Magestore Developer
 */
class AllowClickAndCollect implements OptionSourceInterface
{
    /**
     * Const ALLOW_CLICK_AND_COLLECT_ON_STORE
     *
     * Allow click and collect on store
     */
    const ALLOW_CLICK_AND_COLLECT_ON_STORE = 1;

    /**
     * Const DENIED_CLICK_AND_COLLECT_ON_STORE
     *
     * Denied click and collect on store
     */
    const DENIED_CLICK_AND_COLLECT_ON_STORE = 0;

    /**
     * Function toOptionArray
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Yes'),'value' => self::ALLOW_CLICK_AND_COLLECT_ON_STORE],
            ['label' => __('No'),'value' => self::DENIED_CLICK_AND_COLLECT_ON_STORE],
        ];
    }
}
