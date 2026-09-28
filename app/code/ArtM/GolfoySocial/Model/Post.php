<?php

namespace ArtM\GolfoySocial\Model;

use Magento\Framework\Model\AbstractModel;

class Post extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(
            \ArtM\GolfoySocial\Model\ResourceModel\Post::class
        );
    }
}