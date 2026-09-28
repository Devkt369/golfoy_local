<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Giftvoucher\Api;

use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\Exception\InputException;

/**
 * Interface ImageProcessorInterface
 *
 * @api
 */
interface ImageProcessorInterface
{
    /**
     * Process image and save it to the gift card media directory
     *
     * @param ImageContentInterface $imageContent
     * @return string Relative path of the file where image was saved
     * @throws InputException
     */
    public function processImageContent(ImageContentInterface $imageContent);
}
