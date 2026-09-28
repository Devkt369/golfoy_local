<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\Type\Config as ConfigCacheType;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Saves the selected order statuses. Not a full Stores > Configuration section (no
 * system.xml) - this module only ever reads/writes this one path itself.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'ArtM_InventoryPlanner::settings';

    private const CONFIG_PATH_STATUSES = 'artm_inventoryplanner/general/statuses';

    private const CONFIG_PATH_SOURCES = 'artm_inventoryplanner/general/sources';

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
        $selectedStatuses = (array) $this->getRequest()->getParam('statuses', []);
        $selectedStatuses = array_values(array_filter(array_map('strval', $selectedStatuses)));

        $selectedSources = (array) $this->getRequest()->getParam('sources', []);
        $selectedSources = array_values(array_filter(array_map('strval', $selectedSources)));

        $this->configWriter->save(self::CONFIG_PATH_STATUSES, implode(',', $selectedStatuses));
        $this->configWriter->save(self::CONFIG_PATH_SOURCES, implode(',', $selectedSources));
        $this->cacheTypeList->cleanType(ConfigCacheType::TYPE_IDENTIFIER);

        $this->messageManager->addSuccessMessage(__('Settings saved.'));

        return $this->resultRedirectFactory->create()->setPath('artm_inventoryplanner/settings/index');
    }
}
