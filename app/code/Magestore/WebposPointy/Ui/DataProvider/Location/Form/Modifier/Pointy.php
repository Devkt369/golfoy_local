<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Ui\DataProvider\Location\Form\Modifier;

use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magestore\WebposPointy\Api\PointyConfigManagementInterface;
use Magento\Framework\Registry;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Fieldset;

/**
 * Modifier location data
 */
class Pointy implements ModifierInterface
{
    /**
     * @var PointyConfigManagementInterface
     */
    protected $pointyConfigManagement;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * Pointy constructor.
     * @param PointyConfigManagementInterface $pointyConfigManagement
     * @param Registry $coreRegistry
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        PointyConfigManagementInterface $pointyConfigManagement,
        Registry $coreRegistry,
        ArrayManager $arrayManager
    ) {
        $this->pointyConfigManagement = $pointyConfigManagement;
        $this->coreRegistry = $coreRegistry;
        $this->arrayManager = $arrayManager;
    }

    /**
     * Modify data
     *
     * @param array $data
     * @return array
     * @since 100.1.0
     */
    public function modifyData(array $data)
    {
        if (!$this->pointyConfigManagement->isPointyEnabled()) {
            return $data;
        }
        $location = $this->coreRegistry->registry('current_location');
        if (!isset($data[$location->getId()])) {
            return $data;
        }
        $data[$location->getId()]['pointy_integration']['pointy_enable'] = $location->getPointyEnable();
        $data[$location->getId()]['pointy_integration']['pointy_retailer_auth_key']
            = $location->getPointyRetailerAuthKey();
        return $data;
    }

    /**
     * Modify meta
     *
     * @param array $meta
     * @return array
     * @since 100.1.0
     */
    public function modifyMeta(array $meta)
    {
        if (!$this->pointyConfigManagement->isPointyEnabled()) {
            return $meta;
        }
        $location = $this->coreRegistry->registry('current_location');
        if (!$location->getId()) {
            return $meta;
        }
        $pointyNote = __("You did not register this location yet. " .
            "If you want to upload inventory and sales data of this location to Pointy, " .
            "please click the button Signup Pointy.");
        if ($location->getPointyEnable()) {
            $pointyNote = __("You’ve already registered this store location on Pointy.") . "<br>"
                . __("Inventory data is uploaded daily at 0h00.") . "<br>"
                . __("Sale data is uploaded hourly.");
        }
        $meta = array_replace_recursive(
            $meta,
            [
                'pointy_integration' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __("Pointy Integration"),
                                'componentType' => Fieldset::NAME,
                                'collapsible' => false
                            ],
                        ],
                    ],
                    'children' => [
                        'pointy_retailer_auth_key' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label' => __("Retailer Auth Key"),
                                        'additionalInfo' => $pointyNote,
                                        'componentType' => Field::NAME,
                                        'dataType' => Text::NAME,
                                        'formElement' => Input::NAME,
                                        'disabled' => true
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );
        return $meta;
    }
}
