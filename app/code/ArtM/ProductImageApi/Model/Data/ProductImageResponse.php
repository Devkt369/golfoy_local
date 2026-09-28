<?php

declare(strict_types=1);

namespace ArtM\ProductImageApi\Model\Data;

use ArtM\ProductImageApi\Api\Data\ProductImageResponseInterface;
use Magento\Framework\DataObject;

/**
 * Class ProductImageResponse
 *
 * Plain data model returned by the REST endpoint.
 * Extending DataObject ensures Magento's serialiser can introspect
 * the getters and convert them to a JSON response automatically.
 */
class ProductImageResponse extends DataObject implements ProductImageResponseInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSuccess(): bool
    {
        return (bool) $this->getData(self::SUCCESS);
    }

    /**
     * {@inheritdoc}
     */
    public function setSuccess(bool $success): ProductImageResponseInterface
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return (string) $this->getData(self::MESSAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function setMessage(string $message): ProductImageResponseInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function getSku(): string
    {
        return (string) $this->getData(self::SKU);
    }

    /**
     * {@inheritdoc}
     */
    public function setSku(string $sku): ProductImageResponseInterface
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * {@inheritdoc}
     */
    public function getImageUrl(): ?string
    {
        return $this->getData(self::IMAGE_URL);
    }

    /**
     * {@inheritdoc}
     */
    public function setImageUrl(?string $imageUrl): ProductImageResponseInterface
    {
        return $this->setData(self::IMAGE_URL, $imageUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getImageUrls(): array
    {
        return (array) $this->getData(self::IMAGE_URLS);
    }

    /**
     * {@inheritdoc}
     */
    public function setImageUrls(array $imageUrls): ProductImageResponseInterface
    {
        return $this->setData(self::IMAGE_URLS, $imageUrls);
    }
}
