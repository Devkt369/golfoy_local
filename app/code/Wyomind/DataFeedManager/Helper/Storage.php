<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Helper;

/**
 * Class Storage
 * @package Wyomind\DataFeedManager\Helper
 */
class Storage extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $_ioFtp = null;
    public $_ioSftp = null;
    public $_directoryList = null;
    public $_messageManager = null;
    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface|null
     */
    protected $_directoryRead = null;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Framework\App\Helper\Context $context,
        /** @delegation off */
        \Magento\Framework\Filesystem\Directory\ReadFactory $directoryRead
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        $this->_directoryRead = $directoryRead->create($this->getAbsoluteRootDir());
        parent::__construct($context);
    }
    /**
     * @param $useSftp
     * @param $ftpPassive
     * @param $ftpHost
     * @param $ftpLogin
     * @param $ftpPassword
     * @param $ftpDir
     * @param $ftpSsl
     * @param $path
     * @param $file
     * @param null $ftpPort
     * @return bool
     */
    public function ftpUpload($useSftp, $ftpPassive, $ftpHost, $ftpLogin, $ftpPassword, $ftpDir, $ftpSsl, $path, $file, $ftpPort = null)
    {
        if ($useSftp) {
            $ftp = $this->_ioSftp;
        } else {
            $ftp = $this->_ioFtp;
        }
        $rtn = false;
        try {
            $host = str_replace(["ftp://", "ftps://"], "", (string) $ftpHost);
            if ($useSftp && $ftpPort != null) {
                $host .= ":" . $ftpPort;
            }
            $ftp->open([
                'host' => $host,
                'port' => $ftpPort,
                // only ftp
                'user' => $ftpLogin,
                'username' => $ftpLogin,
                // only sftp
                'password' => $ftpPassword,
                'timeout' => '120',
                'path' => $ftpDir,
                'passive' => $ftpPassive,
                // only ftp
                'ssl' => !$useSftp && $ftpSsl,
            ]);
            if ($useSftp) {
                $ftp->cd($ftpDir);
            }
            if (!$useSftp && $ftp->write($file, $this->getAbsoluteRootDir() . $path . "/" . $file)) {
                $this->_messageManager->addSuccess(sprintf(__("File '%s' successfully uploaded on %s"), $file, $ftpHost) . ".");
                $rtn = true;
            } elseif ($useSftp && $ftp->write($file, $this->getAbsoluteRootDir() . $path . "/" . $file)) {
                $this->_messageManager->addSuccess(sprintf(__("File '%s' successfully uploaded on %s"), $file, $ftpHost) . ".");
                $rtn = true;
            } else {
                $this->_messageManager->addError(sprintf(__("Unable to upload '%s'on %s"), $file, $ftpHost) . ".");
                $rtn = false;
            }
        } catch (\Exception $e) {
            $this->_messageManager->addError(__("Ftp upload error : ") . $e->getMessage());
        }
        $ftp->close();
        return $rtn;
    }
    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getAbsoluteRootDir()
    {
        return $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
    }
}