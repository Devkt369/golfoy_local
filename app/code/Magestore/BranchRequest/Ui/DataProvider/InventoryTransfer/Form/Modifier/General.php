<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Ui\DataProvider\InventoryTransfer\Form\Modifier;

/**
 * Class BranchRequest Related
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class General extends \Magestore\BranchRequest\Ui\DataProvider\Form\Modifier\AbstractModifier
{
    protected $_opened = false;
    protected $_groupContainer = 'general_information';
    protected $_groupLabel = 'General Information';
    protected $_sortOrder = 100;

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return [];
    }

    /**
     * Get Opened
     *
     * @return bool
     * @SuppressWarnings(PHPMD)
     */
    public function getOpened()
    {
        return true;
    }

    /**
     * Get Sort Order
     *
     * @return string
     */
    public function getSortOrder()
    {
        return '5';
    }

    /**
     * Get Is Required
     *
     * @return false
     */
    public function getIsRequired()
    {
        return false;
    }

    /**
     * Modify data
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $data = array_replace_recursive(
            $data,
            $this->getData()
        );
        return $data;
    }

    /**
     * Prepare Meta
     *
     * @param mixed $meta
     * @return array
     */
    public function prepareMeta($meta)
    {
        $meta = array_replace_recursive(
            $meta,
            $this->getFieldsMap()
        );

        return $meta;
    }

    /**
     * Modify Meta
     *
     * @param array $meta
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_replace_recursive(
            $meta,
            [
                $this->_groupContainer => [
                    'children' => $this->getGeneralChildren(),
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __($this->getGroupLabel()),
                                'collapsible' => $this->getCollapsible(),
                                'dataScope' => 'data',
                                'visible' => $this->getVisible(),
                                'opened' => $this->getOpened(),
                                'componentType' => 'fieldset',
                                'sortOrder' => $this->getSortOrder()
                            ],
                        ],
                    ],
                ],
            ]
        );
        $meta = $this->prepareMeta($meta);
        return $meta;
    }

    /**
     * Retrieve child meta configuration
     *
     * @return array
     */
    public function getGeneralChildren()
    {
        $children = [
            'reason' => $this->getReasonField()
        ];
        return $children;
    }

    /**
     * Reason field
     *
     * @return array
     */
    public function getReasonField()
    {
        $field = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'field',
                        'dataType' => 'string',
                        'formElement' => 'textarea',
                        'elementTmpl' => $this->getModifyTmpl('textarea'),
                        'required' => $this->getIsRequired(),
                        'label' => __('Reason'),
                        'sortOrder' => 50,
                        'validation' => [
                        ],
                    ],
                ],
            ],
        ];
        return $field;
    }

    /**
     * Get Fields Map
     *
     * @return array
     */
    public function getFieldsMap()
    {
        return [
            'general_information' =>
                [
                    'reason',
                ],
        ];
    }
}
