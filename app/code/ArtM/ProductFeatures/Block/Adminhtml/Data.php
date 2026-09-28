<?php
namespace ArtM\ProductFeatures\Block\Adminhtml;

class Data extends \Magento\Backend\Block\Widget\Grid\Container
{

	protected function _construct()
	{
		$this->_controller = 'adminhtml_post';
		$this->_blockGroup = 'ArtM_ProductFeatures';
		$this->_headerText = __('Data');
		$this->_addButtonLabel = __('Add New Product Features List');
		parent::_construct();
	}
}