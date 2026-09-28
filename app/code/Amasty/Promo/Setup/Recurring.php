<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Setup;

use Amasty\Promo\Api\Data\GiftRuleInterface;
use Amasty\Promo\Model\ResourceModel\Rule;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\SalesRule\Api\Data\RuleInterface;

class Recurring implements InstallSchemaInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var MetadataPool
     */
    private $metadata;

    public function __construct(
        MetadataPool $metadata,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->metadata = $metadata;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->createSalesruleFk();
    }

    private function createSalesruleFk()
    {
        $needToAddFK = true;
        /** @var AdapterInterface $adapter */
        $adapter = $this->moduleDataSetup->getConnection();
        $amruleTableName = $this->moduleDataSetup->getTable(Rule::TABLE_NAME);
        $salesruleTableName = $this->moduleDataSetup->getTable('salesrule');
        $foreignKeys = $adapter->getForeignKeys($amruleTableName);
        $linkField = $this->metadata->getMetadata(RuleInterface::class)->getLinkField();
        if ($foreignKeys) {
            foreach ($foreignKeys as $key) {
                if ($key['COLUMN_NAME'] == GiftRuleInterface::SALESRULE_ID) {
                    if ($key['REF_COLUMN_NAME'] != $linkField) {
                        $adapter->dropForeignKey($key['TABLE_NAME'], $key['FK_NAME']);
                    } else {
                        $needToAddFK = false;
                    }
                }
            }
        }

        if ($needToAddFK) {
            $adapter->addForeignKey(
                $adapter->getForeignKeyName(
                    $amruleTableName,
                    GiftRuleInterface::SALESRULE_ID,
                    $salesruleTableName,
                    $linkField
                ),
                $amruleTableName,
                GiftRuleInterface::SALESRULE_ID,
                $salesruleTableName,
                $linkField
            );
        }
    }
}
