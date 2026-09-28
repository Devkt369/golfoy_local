<?php
namespace ArtM\Sizeroll\Block\Adminhtml;

class Data extends \Magento\Backend\Block\Widget\Grid\Container
{

	protected function _construct()
	{
		$this->_controller = 'adminhtml_post';
		$this->_blockGroup = 'ArtM_Sizeroll';
		$this->_headerText = __('Data');
		$this->_addButtonLabel = __('Create New Sizeroll');
		parent::_construct();
	}
}