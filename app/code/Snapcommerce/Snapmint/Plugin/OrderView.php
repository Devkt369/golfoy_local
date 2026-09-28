<?php 
namespace Snapcommerce\Snapmint\Plugin;

class OrderView
{
    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
    {
        $url = $view->getOrderId();


        $view->addButton(
            'order_myaction',
            [
                'label' => __('Refund Order'),
                'class' => 'custom-refund',
                'onclick' => "sendAjaxRequest('{$url}')"
            ]
        );


    }
}