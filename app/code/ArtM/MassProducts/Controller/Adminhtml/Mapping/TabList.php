<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Mapping;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class TabList extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;

    public function __construct(
        Context $context,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_massproducts_column_mapping');

            $items = $connection->fetchAll(
                $connection->select()
                    ->from($table, ['entity_id', 'csv_column', 'attribute_code'])
                    ->order('entity_id ASC')
            );
            // Filter out parent/child columns
            $items = array_filter($items, function($item) {
                $col = strtolower(trim($item['csv_column'] ?? ''));
                return $col !== 'parent' && $col !== 'child';
            });

            return $result->setData([
                'success' => true,
                'items' => array_values($items),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
