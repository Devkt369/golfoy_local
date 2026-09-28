<?php
namespace ArtM\BlogEsg\Block\Adminhtml;

class Data extends \Magento\Backend\Block\Widget\Grid\Container
{

	protected function _construct()
	{
		$this->_controller = 'adminhtml_post';
		$this->_blockGroup = 'ArtM_BlogFaq';
		$this->_headerText = __('Data');
		$this->_addButtonLabel = __('Create New Faq Link');
		parent::_construct();
	}
}