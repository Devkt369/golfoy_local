<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\DropshipSuccess\Ui\DataProvider\DropshipRequest\DataForm\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\UrlInterface;
use Magestore\DropshipSuccess\Model\Locator\LocatorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\Component\Form;

/**
 * Data provider for Configurable panel
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractModifier implements ModifierInterface
{
    const FORM_NAME = 'os_dropship_request_form';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @param UrlInterface $urlBuilder
     * @param LocatorInterface $locator
     * @param RequestInterface $requestInterface
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        UrlInterface $urlBuilder,
        LocatorInterface $locator,
        RequestInterface $requestInterface,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->locator = $locator;
        $this->requestInterface = $requestInterface;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Get Field
     *
     * @param string $lable
     * @param string $componentType
     * @param bool $visible
     * @param string $dataType
     * @param string $formElement
     * @param array $validation
     * @param string|null $notice
     * @param array|null $options
     * @return \array[][][]
     */
    public function getField(
        $lable,
        $componentType,
        $visible,
        $dataType,
        $formElement,
        $validation = [],
        $notice = null,
        $options = null
    ) {
        $container = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => $lable,
                        'componentType' => $componentType,
                        'visible' => $visible,
                        'dataType' => $dataType,
                        'formElement' => $formElement,
                        'validation' => $validation,
                        'notice' => $notice,
                        'options' => $options
                    ]
                ]
            ]
        ];
        return $container;
    }

    /**
     * Get Modal Button
     *
     * @param string $buttonName
     * @param string $formElement
     * @param string $componentType
     * @param string $component
     * @param array $action
     * @param string $title
     * @param string|null $provider
     * @return \array[][][][]
     */
    public function getModalButton(
        $buttonName,
        $formElement = 'container',
        $componentType = 'container',
        $component = 'Magento_Ui/js/form/components/button',
        $action = [],
        $title = '',
        $provider = null
    ) {
        return [
            $buttonName => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => $formElement,
                            'componentType' => $componentType,
                            'component' => $component,
                            'actions' => $action,
                            'title' => $title,
                            'provider' => $provider,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns text column configuration for the dynamic grid
     *
     * @param string $dataScope
     * @param bool $fit
     * @param string $label
     * @param int $sortOrder
     * @return array
     */
    public function getTextColumn($dataScope, $fit, $label, $sortOrder)
    {
        $column = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Form\Field::NAME,
                        'formElement' => Form\Element\Input::NAME,
                        'elementTmpl' => 'ui/dynamic-rows/cells/text',
                        'dataType' => Form\Element\DataType\Text::NAME,
                        'dataScope' => $dataScope,
                        'fit' => $fit,
                        'label' => __($label),
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];
        return $column;
    }

    /**
     * Add Button
     *
     * @param string $buttonTitle
     * @param array $actions
     * @param string|null $redirectUrl
     * @return array
     */
    public function addButton($buttonTitle = '', $actions = [], $redirectUrl = null)
    {
        $button = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'component' => 'Magento_Ui/js/form/components/button',
                        'title' => __($buttonTitle),
                        'actions' => $actions
                    ],
                ],
            ],
        ];
        if ($redirectUrl) {
            $button['arguments']['data']['config']['redirectUrl'] = $redirectUrl;
        }
        return $button;
    }
}
