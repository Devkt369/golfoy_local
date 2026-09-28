<?php

namespace Wyomind\DataFeedManager\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class AddFtpSsl implements SchemaPatchInterface, PatchRevertableInterface
{

    protected $schemaSetup;


    const version = "11.6.0";
    protected $dbVersion = 0;
    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup,
        ModuleResource       $moduleResource
    ) {

        $this->schemaSetup = $schemaSetup;
        $this->dbVersion = $moduleResource->getDbVersion("Wyomind_DataFeedManager");
    }


    public function apply()
    {

        if (version_compare($this->dbVersion, self::version) >= 0) {
            $this->schemaSetup->getConnection()->startSetup();
            $installer = $this->schemaSetup;

            $installer->getConnection()->addColumn(
                $installer->getTable('datafeedmanager_feeds'),
                'ftp_ssl',
                ['type' => Table::TYPE_INTEGER, 'default' => '0', 'length' => 1, "comment" => "Enable SSL"]
            );

            $this->schemaSetup->getConnection()->endSetup();
        }
    }


    /**
     * @inheritdoc
     */
    public function revert()
    {
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

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
