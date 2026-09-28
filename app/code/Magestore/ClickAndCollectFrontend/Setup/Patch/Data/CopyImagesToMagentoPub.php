<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Setup\Patch\Data;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magestore\Webpos\Model\Deploy\DeployService;
use Psr\Log\LoggerInterface;

/**
 * Copy Images To Magento Pub Media
 */
class CopyImagesToMagentoPub implements DataPatchInterface
{
    /**
     * @var DirectoryList
     */
    protected $_dir;
    /**
     * @var Reader
     */
    protected $reader ;
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var DeployService
     */
    protected $deployService;

    /**
     * @var Filesystem\DriverInterface
     */
    protected $driver;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CopyImagesToMagentoPub constructor.
     * @param DirectoryList $dir
     * @param Reader $reader
     * @param Filesystem $filesystem
     * @param DeployService $deployService
     * @param Filesystem\DriverInterface $driver
     * @param LoggerInterface $logger
     */
    public function __construct(
        DirectoryList $dir,
        Reader $reader,
        Filesystem $filesystem,
        DeployService $deployService,
        Filesystem\DriverInterface $driver,
        LoggerInterface $logger
    ) {
        $this->_dir = $dir;
        $this->reader = $reader;
        $this->filesystem = $filesystem;
        $this->deployService = $deployService;
        $this->driver = $driver;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        try {
            $sourceDir = $this->reader->getModuleDir('', 'Magestore_ClickAndCollectFrontend') . '/pub';
            if ($this->driver->isDirectory($sourceDir)) {
                //we will update/save source of this lib at the folder which includes media folder
                $mediaDir = $this->filesystem
                    ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
                    ->getAbsolutePath();
                $toolDir = $this->driver->getParentDirectory($mediaDir).'/';
                //copy new source of this tool
                $this->deployService->xcopy($sourceDir, $toolDir, 0775);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
