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
use Wyomind\DataFeedManager\Model\ResourceModel\Functions\CollectionFactory as FunctionsCollectionFactory;

class ReplaceCustomFunctionsDefinition implements DataPatchInterface, PatchRevertableInterface
{
    const version = "11.1.0";

    protected $coreDate;
    protected $feedsCollectionFactory;
    protected $functionsCollectionFactory;
    private $moduleDataSetup;
    protected $dataVersion = 0;
    protected $state = null;


    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ModuleResource $moduleResource
     */
    public function __construct(
        ModuleDataSetupInterface   $moduleDataSetup,
        ModuleResource             $moduleResource,
        DateTime                   $coreDate,
        FeedsCollectionFactory     $feedsCollectionFactory,
        FunctionsCollectionFactory $functionsCollectionFactory,
        State                      $state
    ) {

        $this->moduleDataSetup = $moduleDataSetup;
        $this->coreDate = $coreDate;
        $this->state = $state;
        $this->feedsCollectionFactory = $feedsCollectionFactory;
        $this->functionsCollectionFactory = $functionsCollectionFactory;
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
            $toReplace = ["dfm_strtoupper", "dfm_strtolower", "dfm_implode", "dfm_html_entity_decode", "dfm_strip_tags", "dfm_htmlentities", "dfm_substr"];
            $replacement = ["wyomind_strtoupper", "wyomind_strtolower", "wyomind_implode", "wyomind_html_entity_decode", "wyomind_strip_tags", "wyomind_htmlentities", "wyomind_substr"];

            $functions = $this->functionsCollectionFactory->create();
            foreach ($functions as $function) {
                $function->setScript(str_replace($toReplace, $replacement, $function->getScript()));
                $function->save();
            }

            $feeds = $this->feedsCollectionFactory->create();
            foreach ($feeds as $feed) {
                $pattern = $feed->getProductPattern();
                $pattern = str_replace($toReplace, $replacement, $pattern);
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
            ReplaceMyPatternWithSkip::class,
            ReplaceCategoriesIndexWithNth::class
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
