<?php

namespace ArtM\RecommendedFilter\Block\Reindex;

class Attribute extends \Magento\Framework\View\Element\Template
{
	public function __construct( 
		\Magento\Framework\View\Element\Template\Context $context,
		array $data = [])
	{
		parent::__construct($context, $data);
	}
	public function newfunction(){
		die('ArtM/RecommendedFilter/Block/Reindex/Attribute');
	}
}

