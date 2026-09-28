<?php
namespace ArtM\CustomProductDesign\Block\Adminhtml\Items\Column;

use Magento\Framework\View\Element\Template;
use ArtM\CustomProductDesign\Model\ResourceModel\Userdata\CollectionFactory;
use Magento\Framework\View\Element\Template\Context;

class CustomPrint extends Template
{
    protected $userdataCollectionFactory;

    public function __construct(
        Context $context,
        CollectionFactory $userdataCollectionFactory,
        array $data = []
    ) {
        $this->userdataCollectionFactory = $userdataCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getUserdataById($id)
    {
        try {
            $collection = $this->userdataCollectionFactory->create();
            $userdata = $collection->addFieldToFilter('id', $id)->getFirstItem();
            return $userdata->getData() ? $userdata : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getCustomPrintImageUrl($imgPath)
    {
        if (!$imgPath) {
            return null;
        }
        
        // Convert relative path to full URL
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl . $imgPath;
    }

    public function checkImageExists($imgPath)
    {
        if (!$imgPath) {
            return false;
        }
        
        // Check if file exists on server
        $mediaPath = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        return $mediaPath->isFile($imgPath);
    }
}