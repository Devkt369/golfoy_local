<?php
/**
 * ArtM ShortVideos Video Resource Model
 *
 * @category  ArtM
 * @package   ArtM_ShortVideos
 * @author    ArtM
 * @copyright Copyright (c) 2025 ArtM
 */

namespace ArtM\ShortVideos\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Video extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('artm_short_videos', 'video_id');
    }
}
