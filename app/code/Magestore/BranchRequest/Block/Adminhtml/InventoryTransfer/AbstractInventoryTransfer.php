<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer;

use Magento\Framework\AuthorizationInterface;

/**
 * Class BranchRequest AbstractInventoryTransfer
 *
 * @SuppressWarnings(PHPMD)
 */
class AbstractInventoryTransfer
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Registry
     *
     * @var Registry
     */
    protected $registry;

    /**
     * @var AuthorizationInterface
     */
    public $authorization;

    /**
     * AbstractInventoryTransfer constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        AuthorizationInterface $authorization
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->request = $context->getRequest();
        $this->registry = $registry;
        $this->authorization = $authorization;
    }

    /**
     * Get Inventory Transfer
     *
     * @return mixed|null
     */
    public function getInventoryTransfer()
    {
        return $this->registry->registry('current_inventory_transfer');
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
        return $this->urlBuilder->getUrl($route, $params);
    }
}
