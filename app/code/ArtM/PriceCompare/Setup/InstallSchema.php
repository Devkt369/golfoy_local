<?php
namespace ArtM\PriceCompare\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (!$setup->tableExists('artm_pricecompare_website')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('artm_pricecompare_website')
            )
                ->addColumn('website_id', Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                ], 'Website ID')
                ->addColumn('name', Table::TYPE_TEXT, 255, ['nullable' => false], 'Website Name')
                ->addColumn('url', Table::TYPE_TEXT, 255, ['nullable' => false], 'Website URL')
                ->addColumn('logo', Table::TYPE_TEXT, 255, ['nullable' => true], 'Logo')
                ->addColumn('price_selectors', Table::TYPE_TEXT, 255, ['nullable' => false], 'Price Selectors')
                ->setComment('Price Compare Websites');
            $setup->getConnection()->createTable($table);
        }

        if (!$setup->tableExists('artm_pricecompare_brand')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('artm_pricecompare_brand')
            )
                ->addColumn('brand_id', Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                ], 'Brand ID')
                ->addColumn('name', Table::TYPE_TEXT, 255, ['nullable' => false], 'Brand Name')
                ->setComment('Price Compare Brands');
            $setup->getConnection()->createTable($table);
        }

        if (!$setup->tableExists('artm_pricecompare_category')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('artm_pricecompare_category')
            )
                ->addColumn('category_id', Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                ], 'Category ID')
                ->addColumn('name', Table::TYPE_TEXT, 255, ['nullable' => false], 'Category Name')
                ->setComment('Price Compare Categories');
            $setup->getConnection()->createTable($table);
        }

        if (!$setup->tableExists('artm_pricecompare_product')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('artm_pricecompare_product')
            )
                ->addColumn('product_id', Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                ], 'Product ID')
                ->addColumn('name', Table::TYPE_TEXT, 255, ['nullable' => false], 'Product Name')
                ->addColumn('sku', Table::TYPE_TEXT, 64, ['nullable' => false], 'SKU')
                ->addColumn('image', Table::TYPE_TEXT, 255, ['nullable' => true], 'Product Image')
                ->addColumn('brand_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false], 'Brand ID')
                ->addColumn('category_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false], 'Category ID')
                ->setComment('Price Compare Products');
            $setup->getConnection()->createTable($table);
        }

        if (!$setup->tableExists('artm_pricecompare_product_website')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('artm_pricecompare_product_website')
            )
                ->addColumn('id', Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                ], 'ID')
                ->addColumn('product_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false], 'Product ID')
                ->addColumn('website_id', Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false], 'Website ID')
                ->setComment('Product to Website Link');
            $setup->getConnection()->createTable($table);
        }

        $setup->endSetup();
    }
}
