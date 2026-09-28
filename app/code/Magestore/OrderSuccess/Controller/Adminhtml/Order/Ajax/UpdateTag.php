<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\OrderSuccess\Controller\Adminhtml\Order\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magestore\OrderSuccess\Controller\Adminhtml\Order\OrderAction;

/**
 * Ajax - UpdateTag
 */
class UpdateTag extends OrderAction implements HttpPostActionInterface
{

    /**
     * Add remove order tag
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = '';
        $orderId = $this->_request->getParam('order_id');
        $tag = $this->_request->getParam('tag');
        $tagColors = [];
        if ($orderId) {
            try {
                if ($tag == 'remove') {
                    $tagColors = $this->tagService->removeOrderTag($orderId);
                } else {
                    $tagColors = $this->tagService->addTagForAnOrder($tag, $orderId);
                }
            } catch (\Exception $e) {
                $messages = __('Can not update tag for the order');
            }
        } else {
            $error = true;
        }
        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error,
            'html' => $this->getTagHtml($tagColors)
        ]);
    }

    /**
     * Get Tag Html
     *
     * @param string $tagColors
     */
    public function getTagHtml($tagColors)
    {
        $html = '';
        $tagColors = explode(',', (string)$tagColors);
        foreach ($tagColors as $tag) {
            $label = $this->tagService->getTagLabel($tag);
            $html.= '<div style="background-color:'.$tag.'"
                     class="admin__control-tag"
                     title="'.$label.'" >
                </div>';
        }
        return $html;
    }
}
