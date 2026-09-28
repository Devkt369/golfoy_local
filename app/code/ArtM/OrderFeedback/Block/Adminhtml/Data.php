<?php
namespace ArtM\OrderFeedback\Block\Adminhtml;

class Data extends \Magento\Backend\Block\Widget\Grid\Container
{

	protected function _construct()
	{
		$this->_controller = 'adminhtml_post';
		$this->_blockGroup = 'ArtM_OrderFeedback';
		$this->_headerText = __('Data');
		$this->_addButtonLabel = __('Add New Feedback');
		parent::_construct();
	}
}