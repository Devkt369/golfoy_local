<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Attribute;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class Save extends Action
{
    const ADMIN_RESOURCE = 'ArtM_PageGroups::main';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Create a catalog attribute (or return existing by code).
     *
     * POST: label, attribute_code(optional)
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        if (!$this->getRequest()->isPost()) {
            return $resultJson->setData(['success' => false, 'message' => __('Invalid request method.')]);
        }

        $label = trim((string)$this->getRequest()->getParam('label', ''));
        $rawCode = trim((string)$this->getRequest()->getParam('attribute_code', ''));
        $code = $this->normalizeCode($rawCode !== '' ? $rawCode : $label);

        if ($label === '') {
            $label = ucwords(str_replace('_', ' ', $code));
        }

        if ($code === '') {
            return $resultJson->setData(['success' => false, 'message' => __('Attribute label or code is required.')]);
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('artm_pg_attributes');

            if (!$connection->isTableExists($table)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Table %1 does not exist. Run setup:upgrade first.', $table),
                ]);
            }

            $existing = $connection->fetchRow(
                $connection->select()
                    ->from($table, ['attribute_id', 'attribute_code', 'label'])
                    ->where('attribute_code = ?', $code)
                    ->limit(1)
            );

            if ($existing) {
                return $resultJson->setData([
                    'success' => true,
                    'item' => $existing,
                    'message' => __('Attribute already exists.'),
                ]);
            }

            $connection->insert($table, [
                'attribute_code' => $code,
                'label' => $label,
            ]);

            $attributeId = (int)$connection->lastInsertId($table);

            return $resultJson->setData([
                'success' => true,
                'item' => [
                    'attribute_id' => $attributeId,
                    'attribute_code' => $code,
                    'label' => $label,
                ],
                'message' => __('Attribute created.'),
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to save attribute: %1', $e->getMessage()),
            ]);
        }
    }

    /**
     * @param string $value
     * @return string
     */
    private function normalizeCode($value)
    {
        $value = strtolower(trim((string)$value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim((string)$value, '_');
    }
}
