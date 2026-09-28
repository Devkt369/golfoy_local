<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Resolver\Product;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\Product;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magestore\Giftvoucher\Model\Product\Type\Giftvoucher;
use Magestore\Giftvoucher\Api\GiftTemplateRepositoryInterface;
use Magestore\Giftvoucher\Api\Data\GiftTemplateInterface;
use Magestore\Giftvoucher\Model\ResourceModel\GiftTemplate\CollectionFactory as CollectionFactory;

/**
 * Post formatting for data in the giftcard templates
 */
class GiftCardTemplates implements ResolverInterface
{
    /**
     * @var GiftTemplateRepositoryInterface
     */
    protected $giftTemplateRepository;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * GiftCardTemplates constructor.
     *
     * @param GiftTemplateRepositoryInterface $giftTemplateRepository
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        GiftTemplateRepositoryInterface $giftTemplateRepository,
        CollectionFactory $collectionFactory
    ) {
        $this->giftTemplateRepository = $giftTemplateRepository;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     *
     * Add formatting for the giftcard templates
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new GraphQlInputException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['model'];

        if ($product->getTypeId() === Giftvoucher::GIFT_CARD_TYPE) {
            $giftCardTemplateIds = $product->getGiftTemplateIds();
            if ($giftCardTemplateIds) {
                $giftCardTemplateIds = explode(',', (string)$giftCardTemplateIds);
            } else {
                $giftCardTemplateIds = [];
            }

            return $this->collectionFactory->create()->addFieldToFilter(
                GiftTemplateInterface::GIFTCARD_TEMPLATE_ID,
                [
                    'in' => $giftCardTemplateIds
                ]
            );
        }

        return [];
    }
}
