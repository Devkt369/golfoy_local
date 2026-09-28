<?php

namespace Wyomind\DataFeedManager\Setup\Patch\Data;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Wyomind\DataFeedManager\Model\ResourceModel\Feeds\CollectionFactory as FeedsCollectionFactory;

class ReplaceCategoriesIndexWithNth implements DataPatchInterface, PatchRevertableInterface
{
    const version = "10.0.0";

    protected $coreDate;
    protected $feedsCollectionFactory;
    private $moduleDataSetup;
    protected $dataVersion = 0;
    protected $state = null;


    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ModuleResource $moduleResource
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ModuleResource           $moduleResource,
        DateTime                 $coreDate,
        FeedsCollectionFactory   $feedsCollectionFactory,
        State                    $state
    ) {

        $this->moduleDataSetup = $moduleDataSetup;
        $this->coreDate = $coreDate;
        $this->state = $state;
        $this->feedsCollectionFactory = $feedsCollectionFactory;
        $this->dataVersion = $moduleResource->getDataVersion("Wyomind_DataFeedManager");
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {

        if (version_compare($this->dataVersion, self::version) >= 0) {
            $this->moduleDataSetup->getConnection()->startSetup();

            try {
                $this->state->setAreaCode(Area::AREA_ADMINHTML);
            } catch (\Exception $e) {
            }
            $re = '/.categories([^|}]+)index="?\'?([0-9]+)"?\'?/';
            $feeds = $this->feedsCollectionFactory->create();
            foreach ($feeds as $feed) {
                $pattern = $feed->getProductPattern();
                $pattern = preg_replace($re, '.categories${1}nth="${2}"', $pattern);
                $feed->setProductPattern($pattern);
                $feed->save();
            }
            $this->moduleDataSetup->getConnection()->endSetup();
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            Init::class,
            ReplaceMyPatternWithSkip::class
        ];
    }

    public function revert()
    {
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
