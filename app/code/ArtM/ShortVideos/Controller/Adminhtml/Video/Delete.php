<?php
/**
 * ArtM ShortVideos Admin Delete Video Controller
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
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
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
     * Constructor
     *
     * @param Context $context
     * @param VideoFactory $videoFactory
     */
    public function __construct(
        Context $context,
        VideoFactory $videoFactory
    ) {
        parent::__construct($context);
        $this->videoFactory = $videoFactory;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');

        if ($id) {
            try {
                $model = $this->videoFactory->create();
                $model->load($id);
                
                if (!$model->getId()) {
                    throw new LocalizedException(__('This video no longer exists.'));
                }
                
                $model->delete();
                $this->messageManager->addSuccessMessage(__('The video has been deleted successfully.'));
                
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting the video.'));
            }
        }

        return $resultRedirect->setPath('*/index/index');
    }
}
