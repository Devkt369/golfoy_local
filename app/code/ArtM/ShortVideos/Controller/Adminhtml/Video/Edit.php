<?php
/**
 * ArtM ShortVideos Admin Edit Video Controller
 *
 * @category  ArtM
 * @package   ArtM_ShortVideos
 * @author    ArtM
 * @copyright Copyright (c) 2025 ArtM
 */

namespace ArtM\ShortVideos\Controller\Adminhtml\Video;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use ArtM\ShortVideos\Model\VideoFactory;
use Magento\Framework\Registry;

class Edit extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'ArtM_ShortVideos::videos';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var VideoFactory
     */
    protected $videoFactory;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param VideoFactory $videoFactory
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        VideoFactory $videoFactory,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->videoFactory = $videoFactory;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->videoFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This video no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/index/index');
            }
        }

        $this->coreRegistry->register('artm_shortvideo', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_ShortVideos::videos');
        $resultPage->getConfig()->getTitle()->prepend(
            $id ? __('Edit Video: %1', $model->getTitle()) : __('New Video')
        );

        return $resultPage;
    }
}
