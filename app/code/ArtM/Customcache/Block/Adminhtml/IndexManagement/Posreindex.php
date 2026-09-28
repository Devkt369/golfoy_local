<?php
namespace ArtM\Customcache\Block\Adminhtml\IndexManagement;

use Magento\Backend\Block\Template;

class Posreindex extends Template
{
    public function _construct()
    {
        parent::_construct();
        error_log("Posreindex block instantiated");
    }
}
