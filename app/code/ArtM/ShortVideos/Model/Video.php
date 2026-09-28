<?php
/**
 * ArtM ShortVideos Video Model
 *
 * @category  ArtM
 * @package   ArtM_ShortVideos
 * @author    ArtM
 * @copyright Copyright (c) 2025 ArtM
 */

namespace ArtM\ShortVideos\Model;

use Magento\Framework\Model\AbstractModel;
use ArtM\ShortVideos\Model\ResourceModel\Video as VideoResource;

class Video extends AbstractModel
{
    /**
     * Video statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    /**
     * Video types
     */
    const TYPE_INSTAGRAM = 'instagram';
    const TYPE_YOUTUBE = 'youtube';
    const TYPE_UPLOAD = 'upload';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(VideoResource::class);
    }

    /**
     * Get available statuses
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_ENABLED => __('Enabled'),
            self::STATUS_DISABLED => __('Disabled')
        ];
    }

    /**
     * Get available video types
     *
     * @return array
     */
    public function getAvailableTypes()
    {
        return [
            self::TYPE_INSTAGRAM => __('Instagram Reel'),
            self::TYPE_YOUTUBE => __('YouTube Short'),
            self::TYPE_UPLOAD => __('Upload Video')
        ];
    }
}
