<?php
namespace ArtM\MassProducts\Model\Import;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

class AttributeSetValidator
{
    private $attributeSetRepository;
    private $searchCriteriaBuilderFactory;
    private $eavConfig;

    public function __construct(
        AttributeSetRepositoryInterface $attributeSetRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        EavConfig $eavConfig
    ) {
        $this->attributeSetRepository = $attributeSetRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->eavConfig = $eavConfig;
    }

    public function validateHeaders(array $headers): array
    {
        foreach ($headers as $header) {
            if ($this->normalizeKey((string)$header) === 'attribute_set') {
                return ['valid' => true];
            }
        }

        return [
            'valid' => false,
            'type' => 'attribute_set_missing_column',
            'message' => "CSV must contain 'attribute_set' column."
        ];
    }

    public function validateRows(array $rows): array
    {
        $attributeSets = $this->getProductAttributeSets();
        $errors = [];
        $missingRows = [];
        $invalidRowNumbers = [];
        $totalRows = count($rows);
        $validRows = 0;
        $setUsage = [];

        foreach ($rows as $row) {
            $rowNumber = (int)($row['row_number'] ?? 0);
            $data = isset($row['data']) && is_array($row['data']) ? $row['data'] : [];
            $sku = trim((string)($data['sku'] ?? ''));

            $attributeSetValue = '';
            foreach ($data as $key => $value) {
                if ($this->normalizeKey((string)$key) === 'attribute_set') {
                    $attributeSetValue = trim((string)$value);
                    break;
                }
            }

            if ($attributeSetValue === '') {
                $missingRows[] = $rowNumber;
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'message' => 'attribute_set is empty — please add a valid attribute set name',
                ];
                continue;
            }

            $normalized = $this->normalizeKey($attributeSetValue);
            if (!isset($attributeSets[$normalized])) {
                $invalidRowNumbers[] = $rowNumber;
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'message' => "Attribute set '" . $attributeSetValue . "' not found in Magento — check spelling",
                ];
                continue;
            }

            $validRows++;
            $setUsage[$normalized] = $attributeSets[$normalized];
        }

        $invalidCount = $totalRows - $validRows;

        return [
            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidCount,
            'errors' => $errors,
            'missing_attribute_set_rows' => $missingRows,
            'invalid_attribute_set_rows' => $invalidRowNumbers,
            'detected_attribute_sets' => array_values($setUsage),
            'attribute_set_id' => !empty($setUsage) ? (int)reset($setUsage)['attribute_set_id'] : null,
            'attribute_set_name' => !empty($setUsage) ? (string)reset($setUsage)['attribute_set_name'] : '',
        ];
    }

    private function getProductAttributeSets(): array
    {
        $entityTypeId = (int)$this->eavConfig->getEntityType(Product::ENTITY)->getEntityTypeId();
        $searchCriteria = $this->searchCriteriaBuilderFactory->create()
            ->addFilter('entity_type_id', $entityTypeId)
            ->create();
        $items = $this->attributeSetRepository->getList($searchCriteria)->getItems();

        $result = [];
        foreach ($items as $item) {
            $name = (string)$item->getAttributeSetName();
            $result[$this->normalizeKey($name)] = [
                'attribute_set_id' => (int)$item->getAttributeSetId(),
                'attribute_set_name' => $name,
            ];
        }

        return $result;
    }

    private function normalizeKey(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }
}
