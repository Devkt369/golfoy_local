<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Attributesort;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
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
            $setId = (int)$this->getRequest()->getParam('attribute_set_id', 0);
            $codes = (array)$this->getRequest()->getParam('attribute_codes', []);
            $setId = 0;

            $connection = $this->resource->getConnection();
            $sortTable = $connection->getTableName('artm_massproducts_attr_sort');
            if (!$connection->isTableExists($sortTable)) {
                throw new LocalizedException(__('Attribute sort table is missing. Please run setup:upgrade.'));
            }

            $connection->delete($sortTable, ['attribute_set_id = ?' => $setId]);

            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $order = 1;
            foreach ($codes as $code) {
                $attributeCode = trim((string)$code);
                if ($attributeCode === '') {
                    continue;
                }

                $connection->insert(
                    $sortTable,
                    [
                        'attribute_set_id' => $setId,
                        'attribute_code' => $attributeCode,
                        'sort_order' => $order,
                        'updated_at' => $now,
                    ]
                );
                $order++;
            }

            return $result->setData([
                'success' => true,
                'message' => 'Attribute order saved successfully.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
