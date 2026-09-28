<?php
namespace ArtM\CustomPrinting\Block\Adminhtml;

class Data extends \Magento\Backend\Block\Widget\Grid\Container
{

	protected function _construct()
	{
		$this->_controller = 'adminhtml_post';
		$this->_blockGroup = 'ArtM_CustomPrinting';
		$this->_headerText = __('Data');
		$this->_addButtonLabel = __('Create New Attr for custopm printing');
		parent::_construct();
	}
}