<?php

namespace Magestore\OrderSuccessCustomize\Plugin\OrderSuccess\Block\Adminhtml\Order;

use Magestore\OrderSuccess\Block\Adminhtml\Order\View;

/**
 * OrderSuccessCustomize - Order - ViewPlugin
 */
class ViewPlugin
{
    /**
     * Around Get Back Url
     *
     * @param View $subject
     * @param \Closure $proceed
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetBackUrl(
        View $subject,
        \Closure $proceed
    ) {
        if ($subject->getRequest()->getParam('customer_id')) {
            return $subject->getUrl('customer/index/edit', ['id'=> $subject->getRequest()->getParam('customer_id')]);
        }
        if ($subject->getOrder() && $subject->getOrder()->getBackUrl()) {
            return $subject->getOrder()->getBackUrl();
        }

        return $subject->getUrl('sales/*/');
    }
}
