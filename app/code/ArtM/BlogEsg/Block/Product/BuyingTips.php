<?php

namespace ArtM\BlogEsg\Block\Product;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use ArtM\BlogEsg\Helper\Data as BlogHelper;
use Magento\Framework\Locale\CurrencyInterface;

class BuyingTips extends AbstractProduct
{
    /**
     * @var AttributeSetRepositoryInterface
     */
    protected $attributeSetRepository;

    /**
     * @var BlogHelper
     */
    protected $blogHelper;

    /**
     * @param Context $context
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param BlogHelper $blogHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        AttributeSetRepositoryInterface $attributeSetRepository,
        BlogHelper $blogHelper,
        array $data = []
    ) {
        $this->attributeSetRepository = $attributeSetRepository;
        $this->blogHelper = $blogHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get Buying Tips Data based on product attribute set
     *
     * @return array
     */
    public function getBuyingTipsData()
    {
        $product = $this->getProduct();
        if (!$product || !$product->getAttributeSetId()) {
            return [];
        }

        try {
            $attributeSet = $this->attributeSetRepository->get($product->getAttributeSetId());
            $attributeSetName = $attributeSet->getAttributeSetName();
            $data = $this->blogHelper->getrow(strtolower($attributeSetName));

            if ($data && isset($data[0])) {
                return $data[0];
            }
        } catch (\Exception $e) {
            // Log error if needed
        }

        return [];
    }

    /**
     * Get Static Block HTML
     *
     * @param string $identifier
     * @return string
     */
    public function getCmsBlockHtml($identifier)
    {
        if (!$identifier) {
            return '';
        }
        return $this->getLayout()
            ->createBlock(\Magento\Cms\Block\Block::class)
            ->setBlockId($identifier)
            ->toHtml();
    }
}
