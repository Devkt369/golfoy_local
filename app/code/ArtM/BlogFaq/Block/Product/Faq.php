<?php

namespace ArtM\BlogFaq\Block\Product;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use ArtM\BlogFaq\Helper\Data as FaqHelper;

class Faq extends AbstractProduct
{
    /**
     * @var AttributeSetRepositoryInterface
     */
    protected $attributeSetRepository;

    /**
     * @var FaqHelper
     */
    protected $faqHelper;

    /**
     * @param Context $context
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param FaqHelper $faqHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        AttributeSetRepositoryInterface $attributeSetRepository,
        FaqHelper $faqHelper,
        array $data = []
    ) {
        $this->attributeSetRepository = $attributeSetRepository;
        $this->faqHelper = $faqHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get FAQ Data based on product attribute set
     *
     * @return array
     */
    public function getFaqData()
    {
        $product = $this->getProduct();
        if (!$product || !$product->getAttributeSetId()) {
            return [];
        }

        try {
            $attributeSet = $this->attributeSetRepository->get($product->getAttributeSetId());
            $attributeSetName = $attributeSet->getAttributeSetName();
            $data = $this->faqHelper->getrow(strtolower($attributeSetName));

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

    /**
     * Get Product Video URL
     *
     * @return string
     */
    public function getVideoUrl()
    {
        $product = $this->getProduct();
        $video = $product->getVideo();

        if ($product->getData('video') != '') {
            // Ideally use media URL from store manager, but for now matching existing logic with improvements
            // If $video is just a filename, prepend the base url.
            // If it's a full URL, return as is (logic in phtml assumed it's filename)
            $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            return $mediaUrl . 'product_videos/' . $video;
        }
        return '';
    }
}
