<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Reads the Settings page's saved column-grouping config (which Dashboard columns
 * are grouped together, plus any manually-added custom columns) - shared by the
 * Settings page's grouping configurator and the Dashboard's column-visibility
 * popup, so both agree on the same group structure.
 */
class ColumnGroupsProvider
{
    private const CONFIG_PATH_COLUMN_GROUPS = 'artm_inventoryplanner/general/column_groups';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array{groups: array<int, array{name:string, columns: string[]}>, customColumns: array<string, string>}
     */
    public function getGroupsData(): array
    {
        $saved = (string) $this->scopeConfig->getValue(self::CONFIG_PATH_COLUMN_GROUPS);
        $decoded = json_decode($saved, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }
        if (!isset($decoded['groups']) || !is_array($decoded['groups'])) {
            $decoded['groups'] = [];
        }
        if (!isset($decoded['customColumns']) || !is_array($decoded['customColumns'])) {
            $decoded['customColumns'] = [];
        }

        return $decoded;
    }

    public function getGroupsJson(): string
    {
        return (string) json_encode($this->getGroupsData());
    }
}
