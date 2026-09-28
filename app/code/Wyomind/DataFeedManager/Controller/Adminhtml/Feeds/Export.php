<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Controller\Adminhtml\Feeds;

/**
 * Class Export
 * @package Wyomind\DataFeedManager\Controller\Adminhtml\Feeds
 */
class Export extends \Wyomind\DataFeedManager\Controller\Adminhtml\Feeds\AbstractFeeds
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\RawFactory|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $feed = $this->dfmModel;
        $feed->load($this->getRequest()->getParam('id'));
        $fields = [];
        $values = [];

        foreach ($feed->getData() as $field => $value) {
            $fields[] = $field;
            if ($field == 'id') {
                $values[] = 'NULL';
            } else {
                $values[] = "'" . str_replace(["'", "\\"], ["''", "\\\\"], (string)$value) . "'";
            }
        }
        $sql = "INSERT INTO {{datafeedmanager_feeds}}(" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ");";

        if ($this->framework->getStoreConfig('datafeedmanager/system/trans_domain_export')) {
            $key = 'dfm-empty-key';
        } else {
            $key = $this->framework->getStoreConfig('datafeedmanager/license/activation_code');
        }

        $content = openssl_encrypt($sql, 'AES-128-ECB', $key);

        return $this->frameworkExport->sendUploadResponse($feed->getName() . '.dfm', $content);
    }
}
