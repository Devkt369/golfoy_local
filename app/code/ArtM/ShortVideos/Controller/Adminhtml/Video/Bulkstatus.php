<?php
/**
 * ArtM ShortVideos Admin Bulk Status Controller
 *
 * @category  ArtM
 * @package   ArtM_ShortVideos
 * @author    ArtM
 * @copyright Copyright (c) 2025 ArtM
 */

namespace ArtM\ShortVideos\Controller\Adminhtml\Video;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use ArtM\ShortVideos\Model\ResourceModel\Video\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class Bulkstatus extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'ArtM_ShortVideos::videos';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $videoIds = $this->getRequest()->getParam('video_ids');
        $status = $this->getRequest()->getParam('status');

        if (!is_array($videoIds) || empty($videoIds)) {
            $this->messageManager->addErrorMessage(__('Please select video(s) to update.'));
            return $resultRedirect->setPath('*/index/index');
        }

        if ($status === null || $status === '') {
            $this->messageManager->addErrorMessage(__('Invalid status value.'));
            return $resultRedirect->setPath('*/index/index');
        }

        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('video_id', ['in' => $videoIds]);
            
            $updatedCount = 0;
            foreach ($collection as $video) {
                $video->setStatus($status);
                $video->save();
                $updatedCount++;
            }
            
            $statusLabel = $status == 1 ? __('enabled') : __('disabled');
            $this->messageManager->addSuccessMessage(
                __('%1 video(s) have been %2 successfully.', $updatedCount, $statusLabel)
            );
            
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e, 
                __('Something went wrong while updating the videos.')
            );
        }

        return $resultRedirect->setPath('*/index/index');
    }
}
