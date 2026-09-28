<?php
namespace ArtM\MassProducts\Model\SingleSkuTemplate;

use Magento\Framework\App\ResourceConnection;

class TemplateService
{
    /** @var ResourceConnection */
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function sanitizeHtml(string $html): string
    {
        $allowedTags = '<div><section><article><header><footer><main><aside><h1><h2><h3><h4><h5><h6><p><span><strong><em><b><i><u><ul><ol><li><table><thead><tbody><tr><th><td><a><img><button><label><input><textarea><select><option><br><hr>';
        $sanitized = strip_tags($html, $allowedTags);

        // Remove inline event handlers and style attributes.
        $sanitized = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $sanitized);
        $sanitized = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $sanitized);

        // Strip javascript: urls from href/src.
        $sanitized = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')/i', ' $1="#"', $sanitized);

        return trim((string)$sanitized);
    }

    public function sanitizeCss(string $css): string
    {
        $clean = preg_replace('/\/\*.*?\*\//s', '', $css);
        $clean = preg_replace('/@import\s+[^;]+;/i', '', (string)$clean);
        $clean = preg_replace('/expression\s*\([^)]*\)/i', '', (string)$clean);
        $clean = preg_replace('/behavior\s*:[^;]+;/i', '', (string)$clean);
        $clean = preg_replace('/url\s*\(\s*["\']?\s*javascript:[^)]+\)/i', 'none', (string)$clean);
        return trim((string)$clean);
    }

    public function hasInvalidTokenSyntax(string $html): bool
    {
        if (strpos($html, '{{') === false && strpos($html, '}}') === false) {
            return false;
        }

        $withoutValid = preg_replace('/{{\s*group:[^}]+}}/i', '', $html);
        if ($withoutValid === null) {
            return true;
        }

        return strpos($withoutValid, '{{') !== false || strpos($withoutValid, '}}') !== false;
    }

    public function getGroupsWithAttributes(int $attributeSetId, bool $enabledOnly = true): array
    {
        $connection = $this->resource->getConnection();
        $layoutTable = $connection->getTableName('artm_single_sku_layout');
        $groupLayoutTable = $connection->getTableName('artm_single_sku_group_layout');
        $attributeTable = $connection->getTableName('eav_attribute');
        $entityTypeTable = $connection->getTableName('eav_entity_type');

        $entityTypeId = (int)$connection->fetchOne(
            $connection->select()
                ->from($entityTypeTable, ['entity_type_id'])
                ->where('entity_type_code = ?', 'catalog_product')
                ->limit(1)
        );

        $select = $connection->select()
            ->from($layoutTable, ['group_name', 'attribute_code', 'sort_order'])
            ->where('attribute_set_id = ?', $attributeSetId)
            ->order('sort_order ASC');
        if ($enabledOnly) {
            $select->where('is_enabled = ?', 1);
        }
        $layoutRows = $connection->fetchAll($select);

        $placementRows = $connection->fetchAll(
            $connection->select()
                ->from($groupLayoutTable, ['group_name', 'is_enabled', 'column_index', 'sort_order'])
                ->where('attribute_set_id = ?', $attributeSetId)
                ->order('sort_order ASC')
        );

        $labelsMap = [];
        $codes = [];
        foreach ($layoutRows as $row) {
            $code = strtolower(trim((string)($row['attribute_code'] ?? '')));
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        if ($codes) {
            $labelRows = $connection->fetchAll(
                $connection->select()
                    ->from($attributeTable, ['attribute_code', 'frontend_label'])
                    ->where('entity_type_id = ?', $entityTypeId)
                    ->where('attribute_code IN (?)', array_values(array_unique($codes)))
            );
            foreach ($labelRows as $labelRow) {
                $code = strtolower(trim((string)($labelRow['attribute_code'] ?? '')));
                if ($code === '') {
                    continue;
                }
                $label = trim((string)($labelRow['frontend_label'] ?? ''));
                $labelsMap[$code] = $label !== '' ? $label : $code;
            }
        }

        $groups = [];

        foreach ($layoutRows as $layoutRow) {
            $groupName = trim((string)($layoutRow['group_name'] ?? ''));
            if ($groupName === '') {
                $groupName = 'Basic Info';
            }
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'group_name' => $groupName,
                    'is_enabled' => 1,
                    'column_index' => 1,
                    'sort_order' => 0,
                    'attributes' => [],
                ];
            }
            $code = strtolower(trim((string)($layoutRow['attribute_code'] ?? '')));
            if ($code === '') {
                continue;
            }
            $groups[$groupName]['attributes'][] = [
                'attribute_code' => $code,
                'label' => $labelsMap[$code] ?? $code,
            ];
        }

        foreach ($placementRows as $placementRow) {
            $groupName = trim((string)($placementRow['group_name'] ?? ''));
            if ($groupName === '') {
                continue;
            }
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'group_name' => $groupName,
                    'is_enabled' => (int)($placementRow['is_enabled'] ?? 1),
                    'column_index' => (int)($placementRow['column_index'] ?? 1),
                    'sort_order' => (int)($placementRow['sort_order'] ?? 0),
                    'attributes' => [],
                ];
                continue;
            }
            $groups[$groupName]['is_enabled'] = (int)($placementRow['is_enabled'] ?? 1);
            $groups[$groupName]['column_index'] = (int)($placementRow['column_index'] ?? 1);
            $groups[$groupName]['sort_order'] = (int)($placementRow['sort_order'] ?? 0);
        }

        $out = array_values($groups);
        usort($out, static function (array $a, array $b): int {
            $colCmp = ((int)$a['column_index']) <=> ((int)$b['column_index']);
            if ($colCmp !== 0) {
                return $colCmp;
            }
            $sortCmp = ((int)$a['sort_order']) <=> ((int)$b['sort_order']);
            if ($sortCmp !== 0) {
                return $sortCmp;
            }
            return strcasecmp((string)$a['group_name'], (string)$b['group_name']);
        });

        return $out;
    }

    public function buildFallbackTemplate(array $groups): string
    {
        $chunks = [];
        foreach ($groups as $group) {
            $name = trim((string)($group['group_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $chunks[] = '<section class="artm-template-group">{{group:' . $name . '}}</section>';
        }
        if (!$chunks) {
            $chunks[] = '<section class="artm-template-group">{{group:Basic Info}}</section>';
        }

        return '<div class="artm-template-layout">' . implode('', $chunks) . '</div>';
    }

    public function renderPreviewHtml(string $templateHtml, array $groups): string
    {
        $groupsByName = [];
        foreach ($groups as $group) {
            $name = trim((string)($group['group_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $groupsByName[strtolower($name)] = $group;
        }

        $rendered = preg_replace_callback('/{{\s*group:([^}]+)}}/i', function (array $matches) use ($groupsByName): string {
            $requested = trim((string)($matches[1] ?? ''));
            $key = strtolower($requested);
            if (!isset($groupsByName[$key])) {
                return '<div class="artm-preview-group artm-preview-group-missing">Group not found: ' . htmlspecialchars($requested, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            $group = $groupsByName[$key];
            $attrs = is_array($group['attributes'] ?? null) ? $group['attributes'] : [];
            $items = [];
            foreach ($attrs as $attr) {
                $label = (string)($attr['label'] ?? $attr['attribute_code'] ?? '');
                $code = (string)($attr['attribute_code'] ?? '');
                $items[] = '<li><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong> <span>(' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . ')</span></li>';
            }
            if (!$items) {
                $items[] = '<li>No attributes in this group.</li>';
            }

            return '<section class="artm-preview-group">'
                . '<h4>' . htmlspecialchars((string)$group['group_name'], ENT_QUOTES, 'UTF-8') . '</h4>'
                . '<ul>' . implode('', $items) . '</ul>'
                . '</section>';
        }, $templateHtml);

        return (string)$rendered;
    }
}
