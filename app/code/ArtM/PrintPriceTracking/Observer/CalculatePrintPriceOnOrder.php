<?php
namespace ArtM\PrintPriceTracking\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class CalculatePrintPriceOnOrder implements ObserverInterface
{
    protected $productRepository;
    protected $orderRepository;
    protected $logger;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order) {
            return;
        }

        $changed = false;

        foreach ($order->getAllItems() as $item) {
            try {
                $productId = $item->getProductId();
                if (!$productId) {
                    continue;
                }

                $options = [];
                $productOptions = $item->getProductOptions();
                if (is_array($productOptions)) {
                    $options = $productOptions['options'] ?? [];
                } else {
                    $raw = $item->getData('product_options');
                    if ($raw) {
                        $decoded = @json_decode($raw, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $options = $decoded['options'] ?? [];
                        } else {
                            $un = @unserialize($raw);
                            if (is_array($un)) {
                                $options = $un['options'] ?? [];
                            }
                        }
                    }
                }

                $hasCustomText = false;
                foreach ($options as $opt) {
                    if (empty($opt['label'])) {
                        continue;
                    }

                    $label = $opt['label'];
                    $hasPrintLabel = stripos($label, 'print') !== false;
                    $hasCustomLabel = preg_match('/\b(custom|logo|text|customization)\b/i', $label);

                    if ($hasPrintLabel || $hasCustomLabel) {
                        $hasCustomText = true;
                        break;
                    }
                }

                if (!$hasCustomText) {
                    continue;
                }

                $qty = (float) ($item->getQtyOrdered() ?: $item->getQty() ?: 1);
                $rowTotalInclTax = (float) $item->getRowTotalInclTax();
                $unitPriceInclTax = $qty ? $rowTotalInclTax / $qty : 0;

                // Load product to get original price (use final price to account for special price)
                $product = $this->productRepository->getById($productId);
                $originalPrice = (float) $product->getFinalPrice($qty);

                $diff = $unitPriceInclTax - $originalPrice;
                if ($diff > 0) {
                    $item->setData('print_price', round($diff, 4));
                    $changed = true;
                }
            } catch (\Exception $e) {
                $this->logger->error('CalculatePrintPriceOnOrder error: ' . $e->getMessage());
            }
        }

        if ($changed) {
            try {
                $this->orderRepository->save($order);
            } catch (\Exception $e) {
                $this->logger->error('Failed to save order after setting print_price: ' . $e->getMessage());
            }
        }
    }
}
