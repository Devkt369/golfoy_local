<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 *
 * Used for helper data
 */
class Data extends AbstractHelper
{
    const URL_TYPE_PWA = 'apps/clickandcollect';

    /**
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->filesystem = $filesystem;
    }

    /**
     * Get Click And Collect App Url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getClickAndCollectAppUrl()
    {
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB)
            . $this->filesystem->getUri(DirectoryList::MEDIA);
        $parentUrl = preg_replace(
            '/' . UrlInterface::URL_TYPE_MEDIA . '\/$|' . UrlInterface::URL_TYPE_MEDIA . '$/',
            '',
            $mediaUrl
        );
        return $parentUrl . self::URL_TYPE_PWA;
    }
}
