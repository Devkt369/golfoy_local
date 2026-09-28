<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Model;

/**
 * local registry
 */
class Storage
{
    /**
     * Free gift quote items with tax
     *
     * @var array
     * @deprecated
     * @see do not use this logic anymore
     */
    public static $cachedFreeGiftsWithTax = [];

    /**
     * Cached price with tax for free gift quote items
     *
     * @var array
     * @deprecated
     * @see do not use this logic anymore
     */
    public static $cachedQuoteItemPricesWithTax = [];

    /**
     * @var bool
     */
    public static $isReorder = false;

    /**
     * @var bool
     */
    private $isQuoteSaveAllowed = true;

    /**
     * @var bool
     */
    private $isQuoteSaveRequired = false;

    /**
     * @var bool
     */
    private $isAutoAddAllowed = true;

    /**
     * @var bool
     */
    private $shouldUpdateCartSection = false;

    /**
     * Set flag for avoid quote saving while Free Gift adding
     * Extra quote saves can cause Magento errors.
     */
    public function restrictQuoteSaving()
    {
        $this->isQuoteSaveAllowed = false;
    }

    public function allowQuoteSaving()
    {
        $this->isQuoteSaveAllowed = true;
    }

    /**
     * Is collect totals process runned from save handler (it means we shouldn't save quote)
     *
     * @return bool
     */
    public function isQuoteSaveAllowed(): bool
    {
        return $this->isQuoteSaveAllowed;
    }

    /**
     * Is quote items was updated and changes should be saved
     *
     * @return bool
     */
    public function isQuoteSaveRequired(): bool
    {
        return $this->isQuoteSaveRequired;
    }

    /**
     * @param bool $isRequired
     */
    public function setIsQuoteSaveRequired(bool $isRequired)
    {
        $this->isQuoteSaveRequired = $isRequired;
    }

    /**
     * Is quote items was updated and changes should be saved
     *
     * @return bool
     */
    public function isAutoAddAllowed(): bool
    {
        return $this->isAutoAddAllowed;
    }

    /**
     * @param bool $flag
     */
    public function setIsAutoAddAllowed(bool $flag)
    {
        $this->isAutoAddAllowed = $flag;
    }

    public function shouldUpdateCartSection(): bool
    {
        return $this->shouldUpdateCartSection;
    }

    public function setShouldUpdateCartSection(bool $shouldUpdateCartSection): void
    {
        $this->shouldUpdateCartSection = $shouldUpdateCartSection;
    }

    public function _resetState(): void
    {
        self::$cachedFreeGiftsWithTax = [];
        self::$cachedQuoteItemPricesWithTax = [];
        self::$isReorder = false;
        $this->isQuoteSaveAllowed = true;
        $this->isQuoteSaveRequired = false;
        $this->isAutoAddAllowed = true;
        $this->shouldUpdateCartSection = false;
    }
}
