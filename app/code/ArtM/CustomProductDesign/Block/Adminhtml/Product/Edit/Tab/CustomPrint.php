<?php
namespace ArtM\CustomProductDesign\Block\Adminhtml\Product\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Framework\App\ResourceConnection;

class CustomPrint extends Template
{
	protected $resource;

	public function __construct(
		Template\Context $context,
		ResourceConnection $resource,
		array $data = []
	) {
		$this->resource = $resource;
		parent::__construct($context, $data);
	}

	/**
	 * Returns array: [image_file => area_count]
	 */
	public function getCustomPrintAreasByImage($productId)
	{
		$result = [];
		if (!$productId) return $result;
		$connection = $this->resource->getConnection();
		$table = $connection->getTableName('artm_customprint_area');
		$select = $connection->select()
			->from($table, ['image', 'cnt' => 'COUNT(*)'])
			->where('product_id = ?', $productId)
			->group('image');
		$rows = $connection->fetchAll($select);
		foreach ($rows as $row) {
			$result[$row['image']] = (int)$row['cnt'];
		}
		return $result;
	}
}
