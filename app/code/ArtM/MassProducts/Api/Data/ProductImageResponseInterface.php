<?php

declare(strict_types=1);

namespace ArtM\MassProducts\Api\Data;

/**
 * Interface ProductImageResponseInterface
 *
 * Represents the response returned after setting a product image.
 */
interface ProductImageResponseInterface
{
    public const SUCCESS     = 'success';
    public const MESSAGE     = 'message';
    public const SKU         = 'sku';
    public const IMAGE_URL   = 'image_url';   // first/base image URL
    public const IMAGE_URLS  = 'image_urls';  // all image URLs

    /**
     * Whether the operation was successful.
     *
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * @param bool $success
     * @return $this
     */
    public function setSuccess(bool $success): self;

    /**
     * Human-readable result message.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self;

    /**
     * The SKU that was processed.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * The public URL of the image that was set.
     *
     * @return string|null
     */
    public function getImageUrl(): ?string;

    /**
     * @param string|null $imageUrl
     * @return $this
     */
    public function setImageUrl(?string $imageUrl): self;

    /**
     * All image URLs assigned (first = base image, rest = gallery).
     *
     * @return string[]
     */
    public function getImageUrls(): array;

    /**
     * @param string[] $imageUrls
     * @return $this
     */
    public function setImageUrls(array $imageUrls): self;
}
