<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Controller\Adminhtml\Feeds;

/**
 * Generate sample action
 */
class Sample extends \Wyomind\DataFeedManager\Controller\Adminhtml\Feeds\AbstractFeeds
{
    /**
     * Execute Action
     */
    public function execute()
    {
        $request = $this->getRequest();
        $id = $request->getParam('id');

        $model = $this->dfmModel;

        $model->setDisplay(true);
        $model->limit = $this->framework->getDefaultConfig('datafeedmanager/system/preview');

        if ($id != 0) {
            try {
                $model->load($id);
                $content = $model->generateFile($request);
                $data = ["data" => $content];
            } catch (\Exception $e) {
                $data = ['error' => __("Unable to generate the data feed\n") . nl2br($e->getMessage())];
            }
            $this->getResponse()->representJson($this->_objectManager->create('Magento\Framework\Json\Helper\Data')->jsonEncode($data));
        }
    }
}
