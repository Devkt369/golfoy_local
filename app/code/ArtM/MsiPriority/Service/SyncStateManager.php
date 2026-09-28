<?php
namespace ArtM\MsiPriority\Service;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class SyncStateManager
{
    private const ATTRIBUTE_CODE = 'vinculum_synced';
    private const ATTRIBUTE_CODE_MAP = 'vinculum_mapped';
    private const STORE_ID = 0;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int|null
     */
    private $attributeId;

    public function __construct(
        ResourceConnection $resourceConnection,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->logger = $logger;
    }

    public function isProductSynced(ProductInterface $product): bool
    {
        $productId = (int) $product->getId();
        if ($productId <= 0) {
            return false;
        }

        // Prefer in-memory value if present (e.g. import that sets attribute).
        $inMemory = $product->getData(self::ATTRIBUTE_CODE);
        if ($inMemory !== null && $inMemory !== '') {
            return (int) $inMemory === 1;
        }

        return $this->isProductSyncedById($productId);
    }

    public function isProductMapped(ProductInterface $product): bool
    {
        $productId = (int) $product->getId();
        if ($productId <= 0) {
            return false;
        }

        // Prefer in-memory value if present (e.g. import that sets attribute).
        $inMemory = $product->getData(self::ATTRIBUTE_CODE_MAP);
        if ($inMemory !== null && $inMemory !== '') {
            return (int) $inMemory === 1;
        }

        return false;
    }

    public function isProductSyncedById(int $productId): bool
    {
        $attributeId = $this->getAttributeId();
        if ($attributeId === null) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalog_product_entity_int');

        $select = $connection->select()
            ->from($table, ['value'])
            ->where('entity_id = ?', $productId)
            ->where('attribute_id = ?', $attributeId)
            ->where('store_id = ?', self::STORE_ID);

        $value = $connection->fetchOne($select);
        return (int) $value === 1;
    }

    public function markProductAsSynced(ProductInterface $product): void
    {
        $productId = (int) $product->getId();
        if ($productId <= 0) {
            return;
        }

        $attributeId = $this->getAttributeId();
        if ($attributeId === null) {
            $this->logger->warning('Cannot mark product as synced: attribute does not exist.', [
                'product_id' => $productId,
                'attribute_code' => self::ATTRIBUTE_CODE,
            ]);
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalog_product_entity_int');

        $data = [
            'entity_id' => $productId,
            'attribute_id' => $attributeId,
            'store_id' => self::STORE_ID,
            'value' => 1,
        ];

        // Upsert so it works for both first-time and re-saving cases.
        $connection->insertOnDuplicate($table, $data, ['value']);

        $this->logger->debug('Marked product as Vinculum synced.', [
            'product_id' => $productId,
            'attribute_code' => self::ATTRIBUTE_CODE,
        ]);
    }
    
    public function markProductAsMapped(ProductInterface $product): void
    {
        $productId = (int) $product->getId();
        if ($productId <= 0) {
            return;
        }

        $attributeId = $this->getMappedAttributeId();
        if ($attributeId === null) {
            $this->logger->warning('Cannot mark product as synced: attribute does not exist.', [
                'product_id' => $productId,
                'attribute_code' => self::ATTRIBUTE_CODE_MAP,
            ]);
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalog_product_entity_int');

        $data = [
            'entity_id' => $productId,
            'attribute_id' => $attributeId,
            'store_id' => self::STORE_ID,
            'value' => 1,
        ];

        // Upsert so it works for both first-time and re-saving cases.
        $connection->insertOnDuplicate($table, $data, ['value']);

        $this->logger->debug('Marked product as Vinculum synced.', [
            'product_id' => $productId,
            'attribute_code' => self::ATTRIBUTE_CODE_MAP,
        ]);
    }

    private function getAttributeId(): ?int
    {
        if ($this->attributeId !== null) {
            return $this->attributeId;
        }

        try {
            $attribute = $this->productAttributeRepository->get(self::ATTRIBUTE_CODE);
            $this->attributeId = (int) $attribute->getAttributeId();
            return $this->attributeId > 0 ? $this->attributeId : null;
        } catch (\Exception $exception) {
            $this->logger->debug('Vinculum sync attribute not found yet.', [
                'attribute_code' => self::ATTRIBUTE_CODE,
                'message' => $exception->getMessage(),
            ]);
            $this->attributeId = null;
            return null;
        }
    }

    private function getMappedAttributeId(): ?int
    {
        if ($this->attributeId !== null) {
            return $this->attributeId;
        }

        try {
            $attribute = $this->productAttributeRepository->get(self::ATTRIBUTE_CODE_MAP);
            $this->attributeId = (int) $attribute->getAttributeId();
            return $this->attributeId > 0 ? $this->attributeId : null;
        } catch (\Exception $exception) {
            $this->logger->debug('Vinculum sync attribute not found yet.', [
                'attribute_code' => self::ATTRIBUTE_CODE,
                'message' => $exception->getMessage(),
            ]);
            $this->attributeId = null;
            return null;
        }
    }
}

