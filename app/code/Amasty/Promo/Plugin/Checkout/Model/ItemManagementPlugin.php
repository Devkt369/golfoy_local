<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Plugin\Checkout\Model;

use Amasty\CheckoutCore\Api\ItemManagementInterface;
use Amasty\Promo\Helper\Item;
use Laminas\Uri\Uri as LaminasUri;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

class ItemManagementPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var Item
     */
    private $helperItem;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var LaminasUri
     */
    private $uri;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        Item $helperItem,
        ObjectManagerInterface $objectManager,
        LaminasUri $uri
    ) {
        $this->cartRepository = $cartRepository;
        $this->helperItem = $helperItem;
        $this->objectManager = $objectManager;
        $this->uri = $uri;
    }

    /**
     * @param ItemManagementInterface $subject
     * @param int $cartId
     * @param int $itemId
     * @param string $formData
     *
     * @return array
     */
    public function beforeUpdate(
        ItemManagementInterface $subject,
        $cartId,
        $itemId,
        $formData
    ) {
        /** @var Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $item = $quote->getItemById($itemId);

        if (!$this->helperItem->isPromoItem($item)) {
            return [$cartId, $itemId, $formData];
        }

        $this->uri->setQuery($formData);
        $params = $this->uri->getQueryAsArray();
        $params['options'] = $item->getBuyRequest()->getOptions() ?? [];
        $this->uri->setQuery($params);
        $formData = $this->uri->getQuery();

        return [$cartId, $itemId, $formData];
    }
}
