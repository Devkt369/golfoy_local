<?php

namespace ArtM\OrderFeedback\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetupFactory;

/**
* Class UpgradeData*/

class UpgradeData implements UpgradeDataInterface

{

 private $salesSetupFactory;

 public function __construct(SalesSetupFactory $salesSetupFactory)

 {
 $this->salesSetupFactory = $salesSetupFactory;
 }

 public function upgrade(

 ModuleDataSetupInterface $setup,

 ModuleContextInterface $context

 ) {
/*
 if (version_compare($context->getVersion(), "2.0.0", "<")) {


 $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

 $salesSetup->addAttribute(

 'order',

 'orderfeedback_comment',

 [

 'type' => 'varchar',

 'length' => 500,

 'visible' => false,

 'required' => false,

 'grid' => true

 ]
 );

     
 }*/

 if (version_compare($context->getVersion(), "12.0.0", "<")) {


 $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

 $salesSetup->addAttribute(

 'order',

 'orderfeedback_comment',

 [

 'type' => 'varchar',

 'length' => 500,

 'visible' => false,

 'required' => false,

 'grid' => true

 ]
 );

     
 }
 }
}