<?php
namespace Mango\Ajaxlayerednavigation\Model\Plugin\CatalogSearch\Layer\Filter;

class Attribute
{
    /**
     * Filter item factory
     *
     * @var \Magento\Catalog\Model\Layer\Filter\ItemFactory
     */
    protected $filterItemFactory;
    /**
     * Constructor
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
    ) {
        $this->filterItemFactory = $filterItemFactory;
    }
    /**
     * Create filter item object
     *
     * @param   string $label
     * @param   mixed $value
     * @param   int $count
     * @return  \Magento\Catalog\Model\Layer\Filter\Item
     */
    protected function _createItem($filter, $label, $value, $count = 0)
    {
        return $this->filterItemFactory->create()
                        ->setFilter($filter)
                        ->setLabel($label)
                        ->setValue($value)
                        ->setCount($count);
    }
}
