<?php
/**
 * Observer to enforce "Customization" field option on product save.
 */
namespace ArtM\CustomProductDesign\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddCustomizationOption implements ObserverInterface
{
    /**
     * @var \Magento\Catalog\Model\Product\OptionFactory
     */
    protected $optionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Constructor.
     */
    public function __construct(
        \Magento\Catalog\Model\Product\OptionFactory $optionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $collectionFactory
    ) {
        $this->optionFactory = $optionFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Execute observer.
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();
        $productId = $product->getId();

        // Skip if no product ID (edge case for unsaved products)
        if (!$productId) {
            return;
        }

        if($product->getCanCustomize() != 1) {
            return;
        }

        // Delete any existing "Customization" option to enforce fixed config
        $collection = $this->collectionFactory->create()
            ->addProductToFilter($productId)
            ->addTitleToResult(1);  // 1 is store id 
            // ->addTitleToFilter('Customization');
        
        $existingCustomizationCount = 0;

        foreach ($collection as $existingOption) {
            if ($existingOption->getTitle() === 'Customization') {
                return;
                $existingOption->delete();
                $existingCustomizationCount++;
            }
        }

        // Define the fixed option data
        $optionData = [
            'product_id' => $productId,
            'type' => 'field',      // Text field type
            'is_require' => 0,      // Required: False
            'sku' => '',            // SKU: empty
            'max_characters' => 0,  // Max Characters: empty (0 = unlimited)
            'price' => 0.00,        // Price: 0
            'price_type' => 'fixed',// Price Type: Fixed
            'title' => 'Customization', // Option Title
            'sort_order' => 1,
        ];

        // Create and save the new option
        $option = $this->optionFactory->create();
        $option->addData($optionData);
        $option->save();

        // Add to product in-memory for current request
        $product->addOption($option);
    }
}