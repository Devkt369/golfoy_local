<?php

namespace ArtM\ProductSwatchesChanges\Model\Config\Source;

class Attributes implements \Magento\Framework\Option\ArrayInterface
{
        
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $collectionFactory;
    protected $attributeCollection;
    protected $storeManager;
    
    /**
     * FilterableAttributeList constructor
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
    }
    public function getAttributeCollection()
    {
        
        if (!$this->attributeCollection) {
            /** @var $collection \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection */
            $this->attributeCollection = $this->collectionFactory->create();
            $this->attributeCollection->setItemObjectClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
                ->addStoreLabel($this->storeManager->getStore()->getId())
                ->setOrder('position', 'ASC');
            $this->attributeCollection
                    ->addIsFilterableFilter()
                    ->load();
        }
        return $this->attributeCollection;
    }
    
    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        
        $array[] = ['value' => '', 'label' => ' - - - '];
        foreach ($this->getAttributeCollection() as $attribute) {
            // echo "<pre>";
            // print_r($attribute);die;
            $array[] = ['value' => $attribute->getAttributeId(), 'label' => $attribute->getFrontendLabel()];
        }
        return $array;
    }
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array[] = ['' => ' - - - '];
        foreach ($this->getAttributeCollection() as $attribute) {
            $array[] = [$attribute->getAttributeId() => $attribute->getFrontendLabel()];
        }
        return $array;
    }
}
