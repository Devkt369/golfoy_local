<?php
/**
 * ArtM ShortVideos Video Collection
 *
 * @category  ArtM
 * @package   ArtM_ShortVideos
 * @author    ArtM
 * @copyright Copyright (c) 2025 ArtM
 */

namespace ArtM\ShortVideos\Model\ResourceModel\Video;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ArtM\ShortVideos\Model\Video;
use ArtM\ShortVideos\Model\ResourceModel\Video as VideoResource;

class Collection extends AbstractCollection
{
    /**
     * ID field name
     *
     * @var string
     */
    protected $_idFieldName = 'video_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Video::class, VideoResource::class);
    }

    /**
     * Get all distinct categories
     *
     * @return array
     */
    public function getAllCategories()
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'category')
            ->distinct(true)
            ->where('category IS NOT NULL')
            ->where('category != ?', '')
            ->order('category ASC');
        
        return $connection->fetchCol($select);
    }
}
