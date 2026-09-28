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
use Wyomind\DataFeedManager\Model\ResourceModel\Store\CollectionFactory;
use Wyomind\DataFeedManager\Model\ResourceModel\Variables\CollectionFactory as VariablesCollectionFactory;

class ReplaceMyPatternWithSkip implements DataPatchInterface, PatchRevertableInterface
{
    const version = "9.0.1";

    protected $coreDate;
    protected $feedsCollectionFactory;
    protected $variablesCollectionFactory;
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
        VariablesCollectionFactory $variablesCollectionFactory,
        FunctionsCollectionFactory $functionsCollectionFactory,
        State                      $state
    ) {

        $this->moduleDataSetup = $moduleDataSetup;
        $this->coreDate = $coreDate;
        $this->state = $state;
        $this->feedsCollectionFactory = $feedsCollectionFactory;
        $this->variablesCollectionFactory = $variablesCollectionFactory;
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
            $re = '/\$myPattern\s*=\s*null;/';
            foreach ($this->feedsCollectionFactory as $feed) {
                $pattern = $feed->getProductPattern();
                preg_match_all($re, $pattern, $matches);
                foreach ($matches[0] as $match) {
                    $pattern = str_replace($match, '$this->skip();', $pattern);
                }
                $feed->setXmlitempattern($pattern);
                $feed->save();
            }
            $variables = $this->variablesCollectionFactory->create();
            foreach ($variables as $variable) {
                $script = $variable->getScript();
                preg_match_all($re, $script, $matches);
                foreach ($matches[0] as $match) {
                    $script = str_replace($match, '$this->skip();', $script);
                }
                $variable->getScript($script);
                $variable->save();
            }
            $functions = $this->functionsCollectionFactory->create();
            foreach ($functions as $function) {
                $script = $function->getScript();
                preg_match_all($re, $script, $matches);
                foreach ($matches[0] as $match) {
                    $script = str_replace($match, '$this->skip();', $script);
                }
                $function->getScript($script);
                $function->save();
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
            Init::class
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
