<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Resolver\GiftCardOptions;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magestore\Giftvoucher\Api\GiftTemplateRepositoryInterface;

/**
 * Class GiftCardTemplate
 *
 * Resolve gift card template for graphql
 */
class GiftCardTemplate implements ResolverInterface
{
    /**
     * @var GiftTemplateRepositoryInterface
     */
    protected $giftTemplateRepository;

    /**
     * Constructor
     *
     * @param GiftTemplateRepositoryInterface $giftTemplateRepository
     */
    public function __construct(
        GiftTemplateRepositoryInterface $giftTemplateRepository
    ) {
        $this->giftTemplateRepository = $giftTemplateRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (isset($value['giftcard_template_id']) && $value['giftcard_template_id']) {
            try {
                return $this->giftTemplateRepository->getById($value['giftcard_template_id']);
            } catch (\Exception $exception) {
                return [];
            }
        }

        return [];
    }
}
