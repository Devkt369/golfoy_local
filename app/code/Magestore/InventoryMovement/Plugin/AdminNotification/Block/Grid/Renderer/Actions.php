<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\AdminNotification\Block\Grid\Renderer;

use Magento\AdminNotification\Block\Grid\Renderer\Actions as CoreActions;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\DataObject;
use Magestore\InventoryMovement\Observer\NotifyUnsyncedPosOrder;
use Magestore\InventoryMovement\Service\PotentialErrorOrders\PotentialErrorOrdersService;

/**
 * Class Actions
 *
 * Render block for notification url
 */
class Actions
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Actions constructor.
     *
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get relative path array
     *
     * @return array
     */
    public function getRelativeBackendPath()
    {
        return [
            NotifyUnsyncedPosOrder::UNSYNCED_POS_ORDER_VIEW_PATH,
            PotentialErrorOrdersService::POTENTIAL_ERROR_ORDERS_URL_PATH
        ];
    }

    /**
     * Before render to change url
     *
     * @param CoreActions $subject
     * @param DataObject $row
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeRender(
        CoreActions $subject,
        DataObject $row
    ) {
        $isRelativeBackEndUrl = false;
        $url = $row->getData('url');
        foreach ($this->getRelativeBackendPath() as $path) {
            if (strpos($url, $path) !== false) {
                $isRelativeBackEndUrl = true;
            }
        }
        if ($isRelativeBackEndUrl) {
            /* Need convert absolute url to relative url for the previous version before fixing */
            if (strpos($url, 'http') !== false) {
                $url = $this->convertAbsoluteUrlToRelative($url);
            }
            $url = $this->urlBuilder->getUrl($url);
            $row->setData('url', $url);
        }
        return [$row];
    }

    /**
     * Convert absolute url to relative for url save wrong before
     *
     * @param string $url
     * @return string
     */
    private function convertAbsoluteUrlToRelative(string $url)
    {
        if (strpos($url, NotifyUnsyncedPosOrder::UNSYNCED_POS_ORDER_VIEW_PATH) !== false) {
            $matches = [];
            preg_match('/webposadmin(.*?)id\/[0-9]*/', $url, $matches);
            if (isset($matches[0])) {
                $url = $matches[0];
            }
            return $url;
        }
        if (strpos($url, PotentialErrorOrdersService::POTENTIAL_ERROR_ORDERS_URL_PATH) !== false) {
            return PotentialErrorOrdersService::POTENTIAL_ERROR_ORDERS_URL_PATH;
        }
        return $url;
    }
}
