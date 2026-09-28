<?php

namespace Wyomind\DataFeedManager\Setup\Patch\Data;

use Magento\Framework\App\State;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Wyomind\DataFeedManager\Model\ResourceModel\Store\CollectionFactory;
use Wyomind\DataFeedManager\Model\ResourceModel\Feeds\CollectionFactory as FeedsCollectionFactory;
use Wyomind\DataFeedManager\Model\ResourceModel\Functions\CollectionFactory as FunctionsCollectionFactory;

class AddSiroopTemplate implements DataPatchInterface, PatchRevertableInterface
{
    const version = "11.4.0";

    protected $coreDate;
    private $moduleDataSetup;
    protected $storeId = 0;
    protected $dataVersion = 0;
    protected $state = null;
    protected $feedsCollectionFactory = null;
    protected $functionsCollectionFactory = null;


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
        State                      $state,
        CollectionFactory          $storeCollectionFactory
    ) {

        $this->moduleDataSetup = $moduleDataSetup;
        $this->coreDate = $coreDate;
        $this->state = $state;
        $this->feedsCollectionFactory = $feedsCollectionFactory;
        $this->functionsCollectionFactory = $functionsCollectionFactory;
        $this->dataVersion = $moduleResource->getDataVersion("Wyomind_DataFeedManager");
        $this->storeId = $storeCollectionFactory->create()->getFirstStoreId();
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {

        if (version_compare($this->dataVersion, self::version) >= 0) {
            $this->moduleDataSetup->getConnection()->startSetup();

            $installer = $this->moduleDataSetup;

            $data = [
                "id" => null,
                "name" => "Siroop",
                "type" => 1,
                "path" => "/feeds/",
                "status" => 0,
                "updated_at" => $this->coreDate->date('Y-m-d H:i:s'),
                "store_id" => $this->storeId,
                "product_pattern" => "<item>
<g:id>{{product.sku}}</g:id>
<g:title>{{product.name}}</g:title>
<s:long_description>{{product.description}}</s:long_description>
<s:siroop_category>{{product.siroop_category}}</s:siroop_category>
<g:image_link>{{parent.image_link index=\"0\"| product.image_link index=\"0\"}}</g:image_link>
<g:additional_image_link>{{parent.image_link index=\"1\" | product.image_link index=\"1\"}}</g:additional_image_link>
<g:additional_image_link>{{parent.image_link index=\"2\" | product.image_link index=\"2\"}}</g:additional_image_link>
<g:additional_image_link>{{parent.image_link index=\"3\" | product.image_link index=\"3\"}}</g:additional_image_link>
<g:additional_image_link>{{parent.image_link index=\"4\" | product.image_link index=\"4\"}}</g:additional_image_link>
<g:additional_image_link>{{parent.image_link index=\"5\" | product.image_link index=\"5\"}}</g:additional_image_link>
<g:additional_image_link/>
<!-- Availability & Price -->
<s:quantity>{{product.qty}}</s:quantity>
<g:price>{{product.price suffix=\" CHF\"}}</g:price>
<s:productvat>1</s:productvat>
<s:warranty>24</s:warranty>
<!-- Unique Product Identifiers-->
<g:gtin>{{product.ean}}</g:gtin>
<g:mpn>{{product.mpn}}</g:mpn>
<s:manufacturer_name>{{producT.manufacturer}}</s:manufacturer_name>
<g:brand>{{product.manufacturer}}</g:brand>
<!-- Products Attributes -->
<s:attribute name=\"Zusatzinformationen\">{{product.short_description}}</s:attribute>
<s:attribute name=\"Grösse\">{{product.size}}</s:attribute>
<s:attribute name=\"Farbe DE\">{{product.color}}</s:attribute>
<s:attribute name=\"Breite\">{{product.width}}</s:attribute>
<s:attribute name=\"Höhe\">{{product.height}}</s:attribute>
<s:attribute name=\"Tiefe\">{{product.depth}}</s:attribute>
<s:attribute name=\"Material\">{{product.material}}</s:attribute>
<s:attribute name=\"Volumen\">{{product.volume}}</s:attribute>
<s:attribute name=\"Geschlecht\">{{product.gender}}</s:attribute>
<s:attribute name=\"Lieferumfang\">{{product.delivery_contents}}</s:attribute>
</item>",
                "category_filter" => 1,
                "categories" => "*",
                "type_ids" => "*",
                "category_type" => 0,
                "visibilities" => "*",
                "attribute_sets" => "*",
                "attributes" => "[]",
                "cron_expr" => '{"days":["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],"hours":["04:00"]}',
                "include_header" => 0,
                "header" => "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<rss version=\"2.0\" xmlns:g=\"http://base.google.com/ns/1.0\"
xmlns:s=\"https://merchants.siroop.ch/\">
<channel>
<title>Products for Siroop Marketplace</title>
<link>https://www.example-shop.ch</link>;
<description>This is a sample feed containing the required and recommended attributes for a variety of different products</description>",
                "footer" => "</channel>
</rss>",
                "encoding" => "UTF-8",
                "enclose_data" => 1,
                "clean_data" => 1,
                "dateformat" => "{f}",
                "ftp_enabled" => 0,
                "use_sftp" => 0,
                "ftp_active" => 0
            ];

            $installer->getConnection()->insert($installer->getTable("datafeedmanager_feeds"), $data);

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
            ReplaceCustomFunctionsDefinition::class
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
