<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Success;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;

class TabMatrix extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;
    private $productRepository;
    private $eavConfig;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        ProductRepositoryInterface $productRepository,
        EavConfig $eavConfig
    )
    {
        parent::__construct($context);
        $this->resource = $resource;
        $this->productRepository = $productRepository;
        $this->eavConfig = $eavConfig;
    }

    public function execute(): Json
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');
            $valueTable = $connection->getTableName('artm_massproducts_draft_values');
            $mediaTable = $connection->getTableName('artm_massproducts_draft_media');
            $productEntityTable = $connection->getTableName('catalog_product_entity');
            $msiSources = $this->getMsiSources();
            $rows = $connection->fetchAll(
                $connection->select()->from($draftTable)->where('status = ?', 'success')->order('entity_id ASC')
            );

            $skuList = array_values(array_filter(array_map(static function ($row) {
                return trim((string)($row['sku'] ?? ''));
            }, $rows)));
            $createdAtBySku = [];
            if (!empty($skuList) && $connection->isTableExists($productEntityTable)) {
                $createdRows = $connection->fetchAll(
                    $connection->select()
                        ->from($productEntityTable, ['sku', 'created_at'])
                        ->where('sku IN (?)', $skuList)
                );
                foreach ($createdRows as $createdRow) {
                    $sku = trim((string)($createdRow['sku'] ?? ''));
                    if ($sku === '') {
                        continue;
                    }
                    $createdAtBySku[$sku] = (string)($createdRow['created_at'] ?? '');
                }
            }

            $draftIds = array_values(array_filter(array_map(static function ($row) {
                return (int)($row['entity_id'] ?? 0);
            }, $rows)));

            $valuesByDraft = [];
            if (!empty($draftIds) && $connection->isTableExists($valueTable)) {
                $valueRows = $connection->fetchAll(
                    $connection->select()
                        ->from($valueTable, ['draft_id', 'attribute_code', 'value'])
                        ->where('draft_id IN (?)', $draftIds)
                        ->where('store_id = ?', 0)
                );

                foreach ($valueRows as $valueRow) {
                    $draftId = (int)$valueRow['draft_id'];
                    if (!isset($valuesByDraft[$draftId])) {
                        $valuesByDraft[$draftId] = [];
                    }
                    $valuesByDraft[$draftId][(string)$valueRow['attribute_code']] = $valueRow['value'];
                }
            }

            $tempMediaByDraft = [];
            if (!empty($draftIds) && $connection->isTableExists($mediaTable)) {
                $mediaBase = rtrim($this->_url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]), '/');
                $mediaRows = $connection->fetchAll(
                    $connection->select()
                        ->from($mediaTable, ['media_id', 'draft_id', 'file_path', 'roles', 'position'])
                        ->where('draft_id IN (?)', $draftIds)
                        ->order('draft_id ASC')
                        ->order('position ASC')
                        ->order('media_id ASC')
                );

                foreach ($mediaRows as $mediaRow) {
                    $did = (int)$mediaRow['draft_id'];
                    if (!isset($tempMediaByDraft[$did])) {
                        $tempMediaByDraft[$did] = [];
                    }

                    $rolesRaw = (string)($mediaRow['roles'] ?? '');
                    $tempMediaByDraft[$did][] = [
                        'media_id' => (int)$mediaRow['media_id'],
                        'url' => $mediaBase . '/' . ltrim((string)$mediaRow['file_path'], '/'),
                        'file_path' => (string)$mediaRow['file_path'],
                        'roles' => $rolesRaw !== '' ? array_values(array_filter(explode(',', $rolesRaw))) : [],
                        'position' => (int)$mediaRow['position'],
                    ];
                }
            }

            $optionLabelCache = [];

            foreach ($rows as &$row) {
                $draftId = (int)($row['entity_id'] ?? 0);
                $baseImage = isset($row['base_image']) ? trim((string)$row['base_image']) : '';
                $productUrl = isset($row['product_url']) ? trim((string)$row['product_url']) : '';
                $productId = isset($row['magento_product_id']) ? $row['magento_product_id'] : '';
                if($productUrl == '' || $productUrl == null || $productId == '' || $productId == null) {
                    try {
                        $product = $this->productRepository->get($row['sku'], true, null, true);
                        $productUrl = (string)$product->getUrlKey();
                        $productId = (string)$product->getId();
                    } catch (\Throwable $e) {
                        // Keep going even if product cannot be loaded.
                    }
                }
                $mapped = [];
                if (!empty($row['mapped_data'])) {
                    $decoded = json_decode((string)$row['mapped_data'], true);
                    if (is_array($decoded)) {
                        $mapped = $decoded;
                    }
                }

                if (isset($valuesByDraft[$draftId])) {
                    foreach ($valuesByDraft[$draftId] as $code => $value) {
                        $mapped[(string)$code] = $value;
                    }
                }

                $mapped = $this->replaceOptionValuesWithLabels($mapped, $optionLabelCache);

                $row['input_payload'] = $mapped;
                $row['input_payload_json'] = json_encode($mapped, JSON_UNESCAPED_UNICODE);
                $row['msi_values'] = $this->extractMsiValues($mapped);
                $row['drafted_by'] = trim((string)($row['drafted_by'] ?? ''));
                $row['ready_by'] = trim((string)($row['ready_by'] ?? ''));
                $row['added_by'] = trim((string)($row['added_by'] ?? ''));
                $row['media_items'] = [];
                $row['base_image'] = $baseImage;
                $row['product_url_key'] = $productUrl;
                $row['magento_product_id'] = $productId;
                $row['product_created_at'] = $createdAtBySku[(string)($row['sku'] ?? '')] ?? '';
                if (isset($tempMediaByDraft[$draftId]) && !empty($tempMediaByDraft[$draftId])) {
                    $row['media_items'] = $tempMediaByDraft[$draftId];
                    continue;
                }

                // $productId = (int)($row['magento_product_id'] ?? 0);
                // if ($productId > 0) {
                //     try {
                //         $product = $this->productRepository->getById($productId, true, null, true);
                //         $mediaBase = rtrim($this->_url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]), '/');
                //         $entries = $product->getMediaGalleryEntries();
                //         if (is_array($entries) && !empty($entries)) {
                //             foreach ($entries as $entry) {
                //                 $file = ltrim((string)$entry->getFile(), '/');
                //                 if ($file === '') {
                //                     continue;
                //                 }

                //                 $types = (array)$entry->getTypes();
                //                 $roles = [];
                //                 if (in_array('image', $types, true)) {
                //                     $roles[] = 'base';
                //                 }
                //                 if (in_array('small_image', $types, true)) {
                //                     $roles[] = 'small';
                //                 }
                //                 if (in_array('thumbnail', $types, true)) {
                //                     $roles[] = 'thumbnail';
                //                 }

                //                 $row['media_items'][] = [
                //                     'url' => $mediaBase . '/catalog/product/' . $file,
                //                     'file_path' => 'catalog/product/' . $file,
                //                     'roles' => $roles,
                //                     'position' => (int)$entry->getPosition(),
                //                 ];
                //             }
                //         }

                //         if (empty($row['media_items'])) {
                //             $img = trim((string)$product->getData('image'));
                //             if ($img !== '' && $img !== 'no_selection') {
                //                 $imgPath = ltrim($img, '/');
                //                 $row['media_items'][] = [
                //                     'url' => $mediaBase . '/catalog/product/' . $imgPath,
                //                     'file_path' => 'catalog/product/' . $imgPath,
                //                     'roles' => ['base']
                //                 ];
                //             }
                //         }
                //     } catch (\Throwable $e) {
                //         // Keep success tab working even if one product cannot be loaded.
                //     }
                // }
            }
            unset($row);

            return $result->setData([
                'success' => true,
                'rows' => $rows,
                'msi_sources' => $msiSources,
            ]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function extractMsiValues(array $mapped): array
    {
        $out = [];

        foreach ($mapped as $code => $value) {
            $attributeCode = (string)$code;
            if (strpos($attributeCode, 'msi_qty__') !== 0) {
                continue;
            }

            $sourceCode = substr($attributeCode, 9);
            if ($sourceCode === '') {
                continue;
            }

            $qty = trim((string)$value);
            if ($qty === '') {
                continue;
            }

            $out[$sourceCode] = $qty;
        }

        return $out;
    }

    private function replaceOptionValuesWithLabels(array $mapped, array &$optionLabelCache): array
    {
        foreach ($mapped as $code => $value) {
            $attributeCode = (string)$code;
            if ($attributeCode === '' || strpos($attributeCode, 'msi_qty__') === 0) {
                continue;
            }

            if (!isset($optionLabelCache[$attributeCode])) {
                $optionLabelCache[$attributeCode] = null;
                try {
                    $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
                    if ($attribute && $attribute->getId()) {
                        $frontendInput = (string)$attribute->getFrontendInput();
                        if ($frontendInput === 'select' || $frontendInput === 'multiselect') {
                            $map = [];
                            $options = (array)$attribute->getSource()->getAllOptions(false);
                            foreach ($options as $option) {
                                $optValue = trim((string)($option['value'] ?? ''));
                                if ($optValue === '') {
                                    continue;
                                }
                                $map[$optValue] = (string)($option['label'] ?? $optValue);
                            }
                            $optionLabelCache[$attributeCode] = [
                                'input' => $frontendInput,
                                'map' => $map,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    $optionLabelCache[$attributeCode] = null;
                }
            }

            $config = $optionLabelCache[$attributeCode];
            if (!is_array($config) || empty($config['map'])) {
                continue;
            }

            $rawValue = (string)$value;
            if ($rawValue === '') {
                continue;
            }

            if (($config['input'] ?? '') === 'multiselect') {
                $parts = array_filter(array_map('trim', explode(',', $rawValue)), static function ($part) {
                    return $part !== '';
                });
                if (empty($parts)) {
                    continue;
                }
                $labels = [];
                foreach ($parts as $part) {
                    $labels[] = $config['map'][$part] ?? $part;
                }
                $mapped[$attributeCode] = implode(', ', $labels);
                continue;
            }

            $mapped[$attributeCode] = $config['map'][$rawValue] ?? $rawValue;
        }

        return $mapped;
    }

    private function getMsiSources(): array
    {
        $connection = $this->resource->getConnection();
        $sourceTable = $connection->getTableName('inventory_source');
        if (!$connection->isTableExists($sourceTable)) {
            return [];
        }

        $schema = $connection->describeTable($sourceTable);
        $select = $connection->select()->from($sourceTable, ['source_code', 'name']);
        if (isset($schema['enabled'])) {
            $select->where('enabled = ?', 1);
        }
        $select->order('source_code ASC');

        $sources = $connection->fetchAll($select);
        $result = [];
        foreach ($sources as $source) {
            $sourceCode = trim((string)($source['source_code'] ?? ''));
            if ($sourceCode === '') {
                continue;
            }

            $sourceName = trim((string)($source['name'] ?? ''));
            $result[] = [
                'source_code' => $sourceCode,
                'name' => $sourceName !== '' ? $sourceName : $sourceCode,
            ];
        }

        return $result;
    }
}
