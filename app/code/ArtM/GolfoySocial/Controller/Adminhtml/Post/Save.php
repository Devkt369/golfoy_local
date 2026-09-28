<?php

namespace ArtM\GolfoySocial\Controller\Adminhtml\Post;

use ArtM\GolfoySocial\Model\PostFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_GolfoySocial::posts';

    public function __construct(
        Context $context,
        private PostFactory $postFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            $this->messageManager->addErrorMessage(
                __('No data was submitted.')
            );

            return $resultRedirect->setPath(
                'golfoysocial/post/index'
            );
        }

        try {

            $post = $this->postFactory->create();

            $post->setData([
                'title' => $data['title'] ?? '',
                'caption' => $data['caption'] ?? '',
                'post_type' => $data['post_type'] ?? 'image',
                'status' => $data['status'] ?? 'draft',
                'product_id' => !empty($data['product_id'])
                    ? (int)$data['product_id']
                    : null,
                'created_at' => date('Y-m-d H:i:s'),
                'published_at' =>
                    ($data['status'] ?? 'draft') === 'published'
                        ? date('Y-m-d H:i:s')
                        : null
            ]);

            $post->save();

            $this->messageManager->addSuccessMessage(
                __('Golfoy post has been saved successfully.')
            );

            return $resultRedirect->setPath(
                'golfoysocial/post/index'
            );

        } catch (\Exception $e) {

            $this->messageManager->addErrorMessage(
                __('Unable to save the post: %1', $e->getMessage())
            );

            return $resultRedirect->setPath(
                'golfoysocial/post/new'
            );
        }
    }
}