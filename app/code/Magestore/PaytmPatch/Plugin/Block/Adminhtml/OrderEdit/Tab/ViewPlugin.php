<?php

/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\PaytmPatch\Plugin\Block\Adminhtml\OrderEdit\Tab;

use One97\Paytm\Block\Adminhtml\OrderEdit\Tab\View;

/**
 * Class ViewPlugin
 *
 * Fix query bug for Paytm Module when use Custom Prefix for POS Order ID
 */
class ViewPlugin
{
    /**
     * Around plugin for canShowTab function
     *
     * @param View $subject
     * @param \Closure $proceed
     * @return bool
     * phpcs:disable Generic.Metrics.NestingLevel
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCanShowTab(
        View $subject,
        \Closure $proceed
    ) {
        $magentoOrderId= $subject->getOrderIncrementId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('paytm_order_data');
        $select = $connection->select();
        if ($connection->isTableExists($tableName)) {
            $sql = $select
                ->from($tableName)
                ->where('`order_id` = ?', $magentoOrderId)
                ->order('id DESC')
                ->__toString();
            $result = $connection->fetchAll($sql);
            if (!empty($result)) {
                if (isset($result[0]['paytm_response']) || trim($result[0]['paytm_response'])=='') {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
