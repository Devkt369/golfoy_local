<?php
namespace ArtM\MassProducts\Model;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

class AttributeSetResolver
{
    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSetRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var EavConfig
     */
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

    /**
     * @param array<int, string> $csvColumns
     * @param array<int, string> $attributeSetValues
     * @return array<string, mixed>
     */
    public function resolve(array $csvColumns, array $attributeSetValues): array
    {
        $attributeSetColumnPresent = false;
        foreach ($csvColumns as $column) {
            if ($this->normalizeKey($column) === 'attribute_set') {
                $attributeSetColumnPresent = true;
                break;
            }
        }

        $attributeSets = $this->getProductAttributeSets();
        $validSets = [];
        $unknownSets = [];

        if ($attributeSetColumnPresent) {
            foreach ($attributeSetValues as $value) {
                $key = $this->normalizeKey($value);
                if ($key === '') {
                    continue;
                }
                if (isset($attributeSets[$key])) {
                    $validSets[$key] = $attributeSets[$key];
                } else {
                    $unknownSets[$key] = $value;
                }
            }
        }

        return [
            'require_attribute_set' => !$attributeSetColumnPresent || !empty($unknownSets) || count($validSets) !== 1,
            'attribute_sets' => array_values($validSets),
            'unknown_sets' => array_values($unknownSets),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getProductAttributeSets(): array
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
