<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magestore\Giftvoucher\Api\ImageProcessorInterface;
use Magento\Framework\Api\Data\ImageContentInterfaceFactory;

/**
 * Class UploadGiftCardTemplateImage
 *
 * Upload custom image to gift card template
 */
class UploadGiftCardTemplateImage implements ResolverInterface
{
    /**
     * @var ImageProcessorInterface
     */
    protected $imageProcessor;

    /**
     * @var ImageContentInterfaceFactory
     */
    protected $imageContentInterfaceFactory;

    /**
     * @var string
     */
    protected $destinationFolder = 'tmp/giftvoucher/images';

    /**
     * UploadGiftCardTemplateImage constructor.
     *
     * @param ImageProcessorInterface $imageProcessor
     * @param ImageContentInterfaceFactory $imageContentInterfaceFactory
     */
    public function __construct(
        ImageProcessorInterface $imageProcessor,
        ImageContentInterfaceFactory $imageContentInterfaceFactory
    ) {
        $this->imageProcessor = $imageProcessor;
        $this->imageContentInterfaceFactory = $imageContentInterfaceFactory;
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
        $imageContent = $this->imageContentInterfaceFactory->create();

        $imageContent->setName($args['name']);
        $imageContent->setType($args['type']);
        $imageContent->setBase64EncodedData($args['base64_encoded_data']);

        $url = $this->imageProcessor->processImageContent($imageContent);
        if ($url) {
            return [
                'url' =>  $url,
                'error_message' => ''
            ];
        } else {
            return [
                'url' =>  '',
                'error_message' => __('Can not upload the file !')
            ];
        }
    }
}
