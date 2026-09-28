<?php
/**
 * ArtM ShortVideos Get Video Controller
 *
 * @category  ArtM
 * @package   ArtM_ShortVideos
 * @author    ArtM
 * @copyright Copyright (c) 2025 ArtM
 */

namespace ArtM\ShortVideos\Controller\Video;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\ShortVideos\Model\VideoFactory;

class Get extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var VideoFactory
     */
    protected $videoFactory;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param VideoFactory $videoFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        VideoFactory $videoFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->videoFactory = $videoFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        $videoId = $this->getRequest()->getParam('video_id');
        
        if (!$videoId) {
            return $result->setData([
                'success' => false,
                'message' => 'Video ID is required'
            ]);
        }

        try {
            $video = $this->videoFactory->create()->load($videoId, 'video_id');
            
            if (!$video->getId() || $video->getStatus() != 1) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Video not found or disabled'
                ]);
            }

            // Get embed URL
            $videoUrl = $video->getVideoUrl();
            $videoType = $video->getVideoType();
            
            if ($videoType === 'instagram') {
                if (preg_match('/instagram\.com\/reel\/([^\/\?]+)/', $videoUrl, $matches)) {
                    $videoUrl = 'https://www.instagram.com/p/' . $matches[1] . '/embed/captioned';
                }
            } elseif ($videoType === 'youtube') {
                if (preg_match('/youtube\.com\/shorts\/([^\/\?]+)/', $videoUrl, $matches)) {
                    $videoUrl = 'https://www.youtube.com/embed/' . $matches[1];
                } elseif (preg_match('/youtu\.be\/([^\/\?]+)/', $videoUrl, $matches)) {
                    $videoUrl = 'https://www.youtube.com/embed/' . $matches[1];
                } elseif (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $videoUrl, $matches)) {
                    $videoUrl = 'https://www.youtube.com/embed/' . $matches[1];
                }
            }

            return $result->setData([
                'success' => true,
                'video' => [
                    'video_id' => $video->getVideoId(),
                    'title' => $video->getTitle(),
                    'video_type' => $videoType,
                    'video_url' => $videoUrl
                ]
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => 'Error loading video: ' . $e->getMessage()
            ]);
        }
    }
}
