<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Filesystem\Io\File as FileIO;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magestore\LoggerApi\Api\ArchiveLogInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class ArchiveLog
 *
 * Archive log to other folder
 */
class ArchiveLog implements ArchiveLogInterface
{
    const BASE_DIR = 'log/magestore/archive/';
    const LOG_DIR = 'log/magestore/';
    const XML_ARCHIVE_LIFE_TIME = 'logger/archive/lifetime';

    /**
     * @var DriverInterface
     */
    protected $fileSystemDrive;

    /**
     * Filesystem instance
     *
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var FileIO
     */
    protected $fileIo;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * ArchiveLog constructor.
     *
     * @param DriverInterface $fileSystemDrive
     * @param Filesystem $filesystem
     * @param FileIO $fileIo
     * @param DateTime $dateTime
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        DriverInterface $fileSystemDrive,
        Filesystem $filesystem,
        FileIO $fileIo,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->fileSystemDrive = $fileSystemDrive;
        $this->fileSystem = $filesystem;
        $this->fileIo = $fileIo;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute the archive
     *
     * @throws FileSystemException
     */
    public function execute(): void
    {
        $archiveDirectory = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)
            ->getAbsolutePath(self::BASE_DIR);
        $logDirectory = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)
            ->getAbsolutePath(self::LOG_DIR);
        if (!$this->fileSystemDrive->isDirectory($archiveDirectory)) {
            $this->fileSystemDrive->createDirectory($archiveDirectory);
        }
        $fileLogs = $this->fileSystemDrive->search('*.log', $logDirectory);
        foreach ($fileLogs as $log) {
            $this->moveLogToArchive($log);
        }
    }

    /**
     * Archive log in the folder archive
     *
     * @param string $log
     * @throws FileSystemException
     */
    protected function moveLogToArchive(string $log)
    {
        $lifeTime = (int) $this->scopeConfig->getValue(self::XML_ARCHIVE_LIFE_TIME);
        $currentFileLogDate = $this->dateTime->gmtDate('Y-m-d');
        $logDirectory = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)
            ->getAbsolutePath(self::LOG_DIR);
        $fileLogOnlyName = str_replace($logDirectory, '', $log);
        $logDate = $this->getDateOfLog($fileLogOnlyName);

        $secs = strtotime($currentFileLogDate) - strtotime($logDate);
        $days = $secs / 86400;

        if ($days >= $lifeTime) {
            $logArchiveChildFolder = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)
                ->getAbsolutePath(self::BASE_DIR). date("Y", strtotime($logDate))
                .'/' . date("m", strtotime($logDate)) .'/';
            if (!$this->fileSystemDrive->isDirectory($logArchiveChildFolder)) {
                $this->fileSystemDrive->createDirectory($logArchiveChildFolder);
            }
            $this->fileIo->mv($log, $logArchiveChildFolder . $fileLogOnlyName);
        }
    }

    /**
     * Get date of the log file
     *
     * @param string $fileName
     * @return string
     */
    protected function getDateOfLog(string $fileName)
    {
        $fileNameArray = explode('_', $fileName);
        if (isset($fileNameArray[0])) {
            return $fileNameArray[0];
        } else {
            return '';
        }
    }
}
