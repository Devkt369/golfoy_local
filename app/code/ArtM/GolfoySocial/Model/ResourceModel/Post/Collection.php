<?php

namespace ArtM\GolfoySocial\Model\ResourceModel\Post;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ArtM\GolfoySocial\Model\Post;
use ArtM\GolfoySocial\Model\ResourceModel\Post as PostResource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            Post::class,
            PostResource::class
        );
    }
}
