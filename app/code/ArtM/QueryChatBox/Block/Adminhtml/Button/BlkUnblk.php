<?php

namespace ArtM\QueryChatBox\Block\Adminhtml\Button;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use ArtM\QueryChatBox\Model\QueryChatBoxFactory;

class BlkUnblk extends Generic implements ButtonProviderInterface
{
    protected $context;

    /**
     * query model
     *
     * @var QueryChatBoxFactory
     */
    protected $queryChatBoxFactory;

    public function __construct(
        Context $context,
        QueryChatBoxFactory $QueryChatBoxFactory
    ) {
        $this->context = $context;
        $this->QueryChatBoxFactory = $QueryChatBoxFactory;
    }

    public function getButtonData()
    {
        $data = [];
        $id = $this->context->getRequest()->getParam('id');
        $model = $this->QueryChatBoxFactory->create()->load($id);
    
        if ($id) {
            // Determine current block status
            $isBlocked = $model->getData('blk_unblk_user') == '1';
            $status = $isBlocked ? 'Unblock' : 'Block';
            $statusParam = $isBlocked ? '0' : '1'; // Set parameter based on the current status
            $message = __('Are you sure you want to %1 this?', $status);
    
            $data = [
                'label' => __($status),
                'class' => 'custom-block-button', // Avoid using the 'block' class
                'on_click' => "confirmSetLocation('{$message}', '{$this->getBlockUrl($statusParam)}')", 
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    public function getBlockUrl($status)
    {
        $id = $this->context->getRequest()->getParam('id');
                
        return $this->getUrl('*/*/blkunblk', [
            'id' => $id,
            'status' => $status
        ]);
    }
}
