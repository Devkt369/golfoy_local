<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Controller\Adminhtml\Settings;

use ArtM\InventoryPlanner\Model\DashboardColumnList;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Cache\Type\Config as ConfigCacheType;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Saves the Settings page's column-grouping configuration - which Dashboard
 * columns are grouped together, under an admin-defined group name. This only
 * persists the config; the Dashboard table doesn't render groups yet.
 */
class SaveColumns extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'ArtM_InventoryPlanner::settings';

    private const CONFIG_PATH_COLUMN_GROUPS = 'artm_inventoryplanner/general/column_groups';

    private WriterInterface $configWriter;

    private TypeListInterface $cacheTypeList;

    public function __construct(Context $context, WriterInterface $configWriter, TypeListInterface $cacheTypeList)
    {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('artm_inventoryplanner/settings/index');

        $decoded = json_decode((string) $this->getRequest()->getParam('column_groups', ''), true);
        if (!is_array($decoded) || !isset($decoded['groups']) || !is_array($decoded['groups'])) {
            $this->messageManager->addErrorMessage(__('Could not save column groups - invalid data.'));

            return $resultRedirect;
        }

        // Custom columns the admin typed in manually (beyond DashboardColumnList::ALL_COLUMNS) -
        // sanitize first so their keys can also be treated as valid group members.
        $customColumnsInput = is_array($decoded['customColumns'] ?? null) ? $decoded['customColumns'] : [];
        $sanitizedCustomColumns = [];
        foreach ($customColumnsInput as $key => $label) {
            $key = (string) $key;
            $label = trim((string) $label);
            if ($key === '' || $label === '') {
                continue;
            }

            $sanitizedCustomColumns[$key] = mb_substr($label, 0, 100);
        }

        $validColumnKeys = array_merge(array_keys(DashboardColumnList::ALL_COLUMNS), array_keys($sanitizedCustomColumns));
        $sanitizedGroups = [];
        foreach ($decoded['groups'] as $group) {
            if (!is_array($group)) {
                continue;
            }

            $name = trim((string) ($group['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $columns = is_array($group['columns'] ?? null) ? $group['columns'] : [];
            $columns = array_values(array_intersect(array_map('strval', $columns), $validColumnKeys));

            $sanitizedGroups[] = ['name' => $name, 'columns' => $columns];
        }

        $this->configWriter->save(
            self::CONFIG_PATH_COLUMN_GROUPS,
            (string) json_encode(['groups' => $sanitizedGroups, 'customColumns' => $sanitizedCustomColumns])
        );
        $this->cacheTypeList->cleanType(ConfigCacheType::TYPE_IDENTIFIER);

        $this->messageManager->addSuccessMessage(__('Column groups saved.'));

        return $resultRedirect;
    }
}
