<?php

namespace ArtM\PageGroups\Controller\Adminhtml\GroupAttribute;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class Listing extends Action
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
     * Return all available attributes.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('artm_pg_attributes');

            if (!$connection->isTableExists($table)) {
                return $resultJson->setData([
                    'success' => true,
                    'items' => [],
                ]);
            }

            $items = $connection->fetchAll(
                $connection->select()
                    ->from($table, ['attribute_id', 'attribute_code', 'label'])
                    ->order('label ASC')
            );

            return $resultJson->setData([
                'success' => true,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to load attributes: %1', $e->getMessage()),
            ]);
        }
    }
}