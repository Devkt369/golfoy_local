<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Ui\Component\Listing\Location\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magestore\WebposPointy\Api\PointyConfigManagementInterface;

/**
 * Column pointy enable
 */
class PointyEnable extends Column
{
    /**
     * @var PointyConfigManagementInterface
     */
    protected $pointyConfigManagement;

    /**
     * PointyEnable constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param PointyConfigManagementInterface $pointyConfigManagement
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        PointyConfigManagementInterface $pointyConfigManagement,
        array $components = [],
        array $data = []
    ) {
        $this->pointyConfigManagement = $pointyConfigManagement;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Produce and return block's html output
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->render();
    }

    /**
     * @inheritDoc
     */
    public function prepare()
    {
        parent::prepare();

        if (!$this->pointyConfigManagement->isPointyEnabled()) {
            $config = $this->getData('config');
            $config['componentDisabled'] = true;
            $this->setData('config', $config);
        }
    }
}
