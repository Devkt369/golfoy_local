<?php
namespace ArtM\RemoveWishlist\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Wishlist\Helper\Data as WishlistHelper;
use Magento\Wishlist\Model\Item;

class Data extends AbstractHelper
{
    /**
     * @var WishlistHelper
     */
    protected $wishlistHelper;

    /**
     * @param Context $context
     * @param WishlistHelper $wishlistHelper
     */
    public function __construct(
        Context $context,
        WishlistHelper $wishlistHelper
    ) {
        $this->wishlistHelper = $wishlistHelper;
        parent::__construct($context);
    }

    /**
     * Get wishlist item for a specific product
     *
     * @param int $productId
     * @return Item|bool
     */
    public function getWishlistItem($productId)
    {
        if (!$this->wishlistHelper->isAllow()) {
            return false;
        }

        $wishlist = $this->wishlistHelper->getWishlist();
        if (!$wishlist) {
            return false;
        }

        $items = $wishlist->getItemCollection();
        foreach ($items as $item) {
            if ($item->getProductId() == $productId) {
                return $item;
            }
        }

        return false;
    }

    /**
     * Retrieve params for removing item from wishlist
     *
     * @param Item $item
     * @return string
     */
    public function getRemoveParams($item)
    {
        return $this->wishlistHelper->getRemoveParams($item);
    }
}
