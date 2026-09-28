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

class ReplaceCustomFunctionsName implements DataPatchInterface, PatchRevertableInterface
{
    const version = "11.4.1";

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
                $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
            } catch (\Exception $e) {
            }

            $functions = $this->functionsCollectionFactory->create();


            foreach ($functions as $function) {
                $searchQuery = "<?php if (!function_exists(";
                if (substr($function->getScript(), 0, strlen($searchQuery)) !== $searchQuery) {
                    $function->setScript(preg_replace("/<\?php\sfunction\s([a-zA-z0-9]+)/", '<?php if (!function_exists("\1")) { function \1', $function->getScript()));
                    $function->setScript(str_replace("?>", "}\n?>", $function->getScript()));
                    $function->save();
                }
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
            ReplaceCategoriesIndexWithNth::class,
            ReplaceCustomFunctionsDefinition::class,
            AddSiroopTemplate::class
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
