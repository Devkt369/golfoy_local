<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Controller\Adminhtml\Dashboard;

use ArtM\InventoryPlanner\Model\DashboardColumnList;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Cache\Type\Config as ConfigCacheType;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Saves which Dashboard columns are checked in the "Columns" popup, then redirects
 * back preserving whatever weeks/filters/sort/page state was active - this is a
 * separate POST form from the page's own GET toolbar (persisting to config_data
 * requires a POST+form_key), so that state is passed through as hidden fields and
 * echoed back into the redirect's query string here.
 */
class SaveColumns extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'ArtM_InventoryPlanner::dashboard';

    private const CONFIG_PATH_VISIBLE_COLUMNS = 'artm_inventoryplanner/general/visible_columns';

    /**
     * Must stay in sync with Block\Adminhtml\Dashboard::QUERY_PARAM_NAMES.
     */
    private const CARRIED_PARAM_NAMES = [
        'from_weeks', 'to_weeks', 'limit', 'page', 'sort', 'dir',
        'attribute_set', 'brand', 'sku', 'product_name', 'live_inventory',
        'from_qty_min', 'from_qty_max',
        'difference_qty_min', 'difference_qty_max',
        'to_qty_min', 'to_qty_max',
        'growth_min', 'growth_max',
    ];

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
        $selected = (array) $this->getRequest()->getParam('visible_columns', []);
        $selected = array_values(array_intersect(
            array_map('strval', $selected),
            array_keys(DashboardColumnList::ALL_COLUMNS)
        ));

        $this->configWriter->save(self::CONFIG_PATH_VISIBLE_COLUMNS, implode(',', $selected));
        $this->cacheTypeList->cleanType(ConfigCacheType::TYPE_IDENTIFIER);

        $this->messageManager->addSuccessMessage(__('Column visibility saved.'));

        $params = [];
        foreach (self::CARRIED_PARAM_NAMES as $name) {
            $value = $this->getRequest()->getParam($name);
            if ($value !== null && $value !== '') {
                $params[$name] = $value;
            }
        }

        return $this->resultRedirectFactory->create()
            ->setPath('artm_inventoryplanner/dashboard/index', ['_query' => $params]);
    }
}
