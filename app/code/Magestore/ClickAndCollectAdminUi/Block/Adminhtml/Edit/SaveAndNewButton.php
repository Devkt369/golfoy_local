<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\ClickAndCollectAdminUi\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Button save and new
 */
class SaveAndNewButton implements ButtonProviderInterface
{
    /**
     * Data for save and new button
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save and New'),
            'class' => 'save',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'clickandcollectadmin_schedule_form.clickandcollectadmin_schedule_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    [
                                        'back' => 'new'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'sort_order' => 90,
        ];
    }
}
