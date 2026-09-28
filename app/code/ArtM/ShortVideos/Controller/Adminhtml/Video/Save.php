<?php
/**
 * ArtM ShortVideos Admin Save Video Controller
 *
 * @category  ArtM
 * @package   ArtM_ShortVideos
 * @author    ArtM
 * @copyright Copyright (c) 2025 ArtM
 */

namespace ArtM\ShortVideos\Controller\Adminhtml\Video;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use ArtM\ShortVideos\Model\VideoFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;

class Save extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'ArtM_ShortVideos::videos';

    /**
     * @var VideoFactory
     */
    protected $videoFactory;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param VideoFactory $videoFactory
     * @param DataPersistorInterface $dataPersistor
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        VideoFactory $videoFactory,
        DataPersistorInterface $dataPersistor,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->videoFactory = $videoFactory;
        $this->dataPersistor = $dataPersistor;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id = $this->getRequest()->getParam('video_id');
            
            try {
                // Handle video upload if present
                $videoPath = $this->handleVideoUpload();
                if ($videoPath) {
                    $data['video_url'] = $videoPath;
                }
                
                // Handle thumbnail upload if present
                $thumbnailPath = $this->handleThumbnailUpload();
                if ($thumbnailPath) {
                    $data['thumbnail_url'] = $thumbnailPath;
                }

                $model = $this->videoFactory->create();
                
                if ($id) {
                    $model->load($id);
                    if (!$model->getId()) {
                        throw new LocalizedException(__('This video no longer exists.'));
                    }
                }
                
                // Ensure show_product_details is set (default to 1 if not present)
                if (!isset($data['show_product_details'])) {
                    $data['show_product_details'] = 1;
                }
                $model->setData($data);
                $model->save();
                
                $this->messageManager->addSuccessMessage(__('Video has been saved successfully.'));
                $this->dataPersistor->clear('artm_shortvideo');
                
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['video_id' => $model->getId()]);
                }
                
                return $resultRedirect->setPath('*/index/index');
                
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the video.'));
            }
            
            $this->dataPersistor->set('artm_shortvideo', $data);
            return $resultRedirect->setPath('*/*/new', ['video_id' => $id]);
        }
        
        return $resultRedirect->setPath('*/index/index');
    }

    /**
     * Handle video file upload
     *
     * @return string|null
     * @throws LocalizedException
     */
    protected function handleVideoUpload()
    {
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'video_upload']);
            $uploader->setAllowedExtensions(['mp4', 'mov', 'avi', 'webm', 'mkv']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $path = $mediaDirectory->getAbsolutePath('artm/shortvideos/videos');
            
            $result = $uploader->save($path);
            
            if (!$result) {
                throw new LocalizedException(__('File cannot be saved to path: %1', $path));
            }
            
            // Return media URL instead of path
            return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) 
                   . 'artm/shortvideos/videos' . $result['file'];
            
        } catch (\Exception $e) {
            if ($e->getCode() != \Magento\MediaStorage\Model\File\Uploader::TMP_NAME_EMPTY) {
                throw new LocalizedException(__($e->getMessage()));
            }
        }
        
        return null;
    }

    /**
     * Handle thumbnail file upload
     *
     * @return string|null
     * @throws LocalizedException
     */
    protected function handleThumbnailUpload()
    {
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'thumbnail_upload']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $path = $mediaDirectory->getAbsolutePath('artm/shortvideos/thumbnails');
            
            $result = $uploader->save($path);
            
            if (!$result) {
                throw new LocalizedException(__('File cannot be saved to path: %1', $path));
            }
            
            return 'artm/shortvideos/thumbnails' . $result['file'];
            
        } catch (\Exception $e) {
            if ($e->getCode() != \Magento\MediaStorage\Model\File\Uploader::TMP_NAME_EMPTY) {
                throw new LocalizedException(__($e->getMessage()));
            }
        }
        
        return null;
    }
}
