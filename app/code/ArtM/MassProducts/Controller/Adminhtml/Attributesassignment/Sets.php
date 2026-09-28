<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Attributesassignment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Sets extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;

    public function __construct(Context $context, ResourceConnection $resource)
    {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $connection = $this->resource->getConnection();
            $setTable = $connection->getTableName('eav_attribute_set');

            $items = $connection->fetchAll(
                $connection->select()
                    ->from($setTable, ['attribute_set_id', 'attribute_set_name'])
                    ->order('attribute_set_name ASC')
            );

            array_unshift($items, [
                'attribute_set_id' => 0,
                'attribute_set_name' => 'Global Attributes',
            ]);

            return $result->setData([
                'success' => true,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
