<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Block\Adminhtml\Location\Edit\Buttons;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magestore\Webpos\Model\Location\Location;
use Magestore\WebposPointy\Api\PointyConfigManagementInterface;

/**
 * Button Signup Pointy
 */
class SignupPointy implements ButtonProviderInterface
{
    /**
     * Url Builder
     *
     * @var Context
     */
    protected $context;

    /**
     * Registry
     *
     * @var Registry
     */
    protected $registry;

    /**
     * @var PointyConfigManagementInterface
     */
    protected $pointyConfigManagement;

    /**
     * SignupPointy constructor.
     * @param Context $context
     * @param Registry $registry
     * @param PointyConfigManagementInterface $pointyConfigManagement
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PointyConfigManagementInterface $pointyConfigManagement
    ) {
        $this->context = $context;
        $this->registry = $registry;
        $this->pointyConfigManagement = $pointyConfigManagement;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrl($route, $params);
    }

    /**
     * Get product
     *
     * @return Location
     */
    public function getLocation()
    {
        return $this->registry->registry('current_location');
    }

    /**
     * @inheritDoc
     */
    public function getButtonData()
    {
        if (!$this->getLocation() || !$this->getLocation()->getId() || $this->getLocation()->getPointyEnable()) {
            return [];
        }

        if (!$this->pointyConfigManagement->isPointyEnabled()) {
            return [];
        }

        $url = $this->getUrl('webpospointyadmin/location/signup', ['id' => $this->getLocation()->getId()]);

        return [
            'label' => __('Sign Up at Pointy'),
            'class' => 'delete',
            'on_click' => sprintf("deleteConfirm(
                    'Are you sure you want to Signup this location onto Pointy system?', 
                    '%s'
                )", $url),
            'sort_order' => 13
        ];
    }
}
