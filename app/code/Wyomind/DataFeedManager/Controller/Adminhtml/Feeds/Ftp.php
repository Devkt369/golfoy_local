<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Controller\Adminhtml\Feeds;

/**
 * Class Ftp
 * @package Wyomind\DataFeedManager\Controller\Adminhtml\Feeds
 */
class Ftp extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Filesystem\Io\Ftp|null
     */
    protected $_ioFtp = null;
    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp|null
     */
    protected $_ioSftp = null;

    /**
     * Ftp constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Filesystem\Io\Ftp $ioFtp
     * @param \Magento\Framework\Filesystem\Io\Sftp $ioSftp
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Filesystem\Io\Ftp $ioFtp,
        \Magento\Framework\Filesystem\Io\Sftp $ioSftp
    ) {
    
        $this->_ioFtp = $ioFtp;
        $this->_ioSftp = $ioSftp;
        parent::__construct($context);
    }

    /**
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Wyomind_DataFeedManager::main');
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $host = $params['ftp_host'];
        $port = $params['ftp_port'];
        $login = $params['ftp_login'];
        $password = $params['ftp_password'];
        $sftp = $params['use_sftp'];
        $active = $params['ftp_active'];
        $ssl = $params['ftp_ssl'];
        $dir = $params['ftp_dir'];

        if ($sftp) {
            $ftp = $this->_ioSftp;
        } else {
            $ftp = $this->_ioFtp;
        }

        try {
            $ftp->open(
                [
                    'host' => $host,
                    'port' => $port,
                    'user' => $login, //ftp
                    'username' => $login, //sftp
                    'password' => $password,
                    'timeout' => '10',
                    'path' => $dir,
                    'passive' => !($active),
                    'ssl' => !$sftp && $ssl
                ]
            );
            $ftp->close();
            $content = __("Connection succeeded");
        } catch (\Exception $e) {
            $content = __("Ftp error : ") . $e->getMessage();
        }
        $this->getResponse()->representJson($this->_objectManager->create('Magento\Framework\Json\Helper\Data')->jsonEncode($content));
    }
}
