<?php

namespace ArtM\Sortbyoptions\Plugin\Model\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\ProductDataMapper;
use ArtM\Sortbyoptions\Model\Elasticsearch\Adapter\DataMapper\AttributesToSort as AttrDataMapper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class ProductDataMapperPlugin
{
    protected $attrDataMapper;
    public $registry;
    public function __construct(AttrDataMapper $attrDataMapper, Registry $registry)
    {
        $this->attrDataMapper = $attrDataMapper;
        $this->registry = $registry;
    }

    public function afterMap(
        ProductDataMapper $subject, 
        $documents,
        $documentData,
        $storeId,
        $context
    ) { 
        $categoryId = '';
        $category = $this->registry->registry('current_category');
        if ($category) {
            $categoryId = $category->getId();
        }
        foreach ($documents as $productId => $document) {
            //@codingStandardsIgnoreLine
            $document = array_merge($document, $this->attrDataMapper->map($productId, $storeId, $categoryId));
            $documents[$productId] = $document;
        }
        
        return $documents;
    }
}