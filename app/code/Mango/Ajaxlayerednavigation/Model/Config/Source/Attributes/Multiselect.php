<?php
namespace Mango\Ajaxlayerednavigation\Model\Config\Source\Attributes;

class Multiselect implements \Magento\Framework\Option\ArrayInterface
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
                    ->addFieldToFilter('frontend_input', ['in' => ['multiselect']])
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
        $array[] = ['value' => 'cat', 'label' => 'Category'];
        foreach ($this->getAttributeCollection() as $attribute) {
            $array[] = ['value' => $attribute->getAttributeCode(), 'label' => $attribute->getFrontendLabel()];
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
        $array[] = ['cat' => 'Category'];
        foreach ($this->getAttributeCollection() as $attribute) {
            $array[] = [$attribute->getAttributeCode() => $attribute->getFrontendLabel()];
        }
        return $array;
    }
}
