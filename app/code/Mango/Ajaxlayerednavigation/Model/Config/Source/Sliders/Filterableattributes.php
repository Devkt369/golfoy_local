<?php
namespace Mango\Ajaxlayerednavigation\Model\Config\Source\Sliders;

class Filterableattributes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $attributeCollection;
    protected $configurableAttributeHandler;
    protected $storeManager;
    protected $collectionFactory;
    /**
     * FilterableAttributeList constructor
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler $configurableAttributeHandler
    ) {
        $this->storeManager = $storeManager;
        $this->configurableAttributeHandler = $configurableAttributeHandler;
        /** @var $collection \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection */
        $this->attributeCollection = $collectionFactory->create();
        $this->attributeCollection->setItemObjectClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
            ->addStoreLabel($storeManager->getStore()->getId())
            ->setOrder('position', 'ASC');
        $this->attributeCollection->getSelect()->columns("additional_table.apply_to");
        $this->attributeCollection
            ->addIsFilterableFilter()
            ->addFieldToFilter('main_table.frontend_input', ['in'=>['select','multiselect']])
            ->load();
    }
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $_array[] = ['value' => '', 'label' => ' - - - '];
        foreach ($this->attributeCollection as $_attribute) {
            if ($this->configurableAttributeHandler->isAttributeApplicable($_attribute)) {
                $_array[] = ['value' => $_attribute->getAttributeCode(), 'label' => $_attribute->getFrontendLabel()];
            }
        }
        return $_array;
    }
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $_array[] = ['' => ' - - - '];
        foreach ($this->attributeCollection as $_attribute) {
            if ($this->configurableAttributeHandler->isAttributeApplicable($_attribute)) {
                $_array[] = [$_attribute->getAttributeCode() => $_attribute->getFrontendLabel()];
            }
        }
        return $_array;
    }
}
