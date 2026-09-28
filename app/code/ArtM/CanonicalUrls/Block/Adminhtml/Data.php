<?php
namespace ArtM\CanonicalUrls\Block\Adminhtml;

class Data extends \Magento\Backend\Block\Widget\Grid\Container
{

	protected function _construct()
	{
		$this->_controller = 'adminhtml_post';
		$this->_blockGroup = 'ArtM_CanonicalUrls';
		$this->_headerText = __('Data');
		$this->_addButtonLabel = __('Add New Canonical Url');
		parent::_construct();
	}
}