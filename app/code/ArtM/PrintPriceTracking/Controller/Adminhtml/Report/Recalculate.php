<?php
namespace ArtM\PrintPriceTracking\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use ArtM\PrintPriceTracking\Model\ResourceModel\Report\CollectionFactory;
use Psr\Log\LoggerInterface;

class Recalculate extends Action
{
    private const MIN_RECALC_DATE = '2026-07-22';

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var RequestInterface */
    private $request;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        OrderRepositoryInterface $orderRepository,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->orderRepository = $orderRepository;
        $this->request = $request;
        $this->logger = $logger;
    }

    public function execute()
    {
        $from = $this->request->getParam('from');
        $to = $this->request->getParam('to');
        $force = (bool)$this->request->getParam('force', false);

        $effectiveFrom = $this->getEffectiveFromDate($from);

        if (!empty($to) && !empty($effectiveFrom) && $to < $effectiveFrom) {
            $this->messageManager->addNoticeMessage(__('No eligible items found for the selected date range.'));
            return $this->_redirect('*/*/index', ['from' => $from, 'to' => $to]);
        }

        $baseCollection = $this->collectionFactory->create();
        $baseCollection->addFieldToSelect(['item_id', 'order_id', 'product_options', 'qty_ordered', 'print_price']);
        $baseCollection->addFieldToFilter('qty_shipped', ['gt' => 0]);

        if (!empty($effectiveFrom)) {
            $baseCollection->addFieldToFilter('created_at', ['gteq' => $effectiveFrom . ' 00:00:00']);
        }

        if (!empty($to)) {
            $baseCollection->addFieldToFilter('created_at', ['lteq' => $to . ' 23:59:59']);
        }

        $alreadyCalculated = 0;
        if (!$force) {
            $alreadyCalculatedCollection = clone $baseCollection;
            $alreadyCalculatedCollection->addFieldToFilter('print_price', ['gt' => 0]);
            $alreadyCalculated = (int)$alreadyCalculatedCollection->getSize();
        }

        $collection = clone $baseCollection;

        // If not forcing, only recalc when print_price is missing or <= 0
        if (!$force) {
            $collection->addFieldToFilter(
                ['print_price', 'print_price'],
                [
                    ['null' => true],
                    ['eq' => '0'],
                    ['lt' => 0]
                ]
            );
        }

        $updated = 0;

        foreach ($collection as $itemRow) {
            if (!$force && (float)$itemRow->getPrintPrice() > 0) {
                $alreadyCalculated++;
                continue;
            }

            try {
                $order = $this->orderRepository->get((int)$itemRow->getOrderId());
                foreach ($order->getAllItems() as $orderItem) {
                    if ((int)$orderItem->getItemId() !== (int)$itemRow->getItemId()) {
                        continue;
                    }

                    $productId = $orderItem->getProductId();
                    if (!$productId) {
                        continue;
                    }

                    // parse options like the observer
                    $options = [];
                    $productOptions = $orderItem->getProductOptions();
                    if (is_array($productOptions)) {
                        $options = $productOptions['options'] ?? [];
                    } else {
                        $raw = $orderItem->getData('product_options');
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

                    $qty = (float) ($orderItem->getQtyOrdered() ?: $orderItem->getQty() ?: 1);
                    $rowTotalInclTax = (float) $orderItem->getRowTotalInclTax();
                    $unitPriceInclTax = $qty ? $rowTotalInclTax / $qty : 0;

                    // load product final price
                    try {
                        $product = $this->_objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface')->getById($productId);
                        $originalPrice = (float) $product->getFinalPrice($qty);
                    } catch (\Exception $e) {
                        $this->logger->error('Recalculate: failed to load product ' . $e->getMessage());
                        $originalPrice = (float) $orderItem->getPrice();
                    }

                    $diff = $unitPriceInclTax - $originalPrice;
                    if ($diff > 0) {
                        $orderItem->setData('print_price', round($diff, 4));
                        $updated++;
                    }

                    break;
                }

                // save order if we changed any item on it
                if ($order->getId()) {
                    try {
                        $this->orderRepository->save($order);
                    } catch (\Exception $e) {
                        $this->logger->error('Recalculate: failed to save order ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Recalculate error: ' . $e->getMessage());
                continue;
            }
        }

        if ($updated > 0) {
            $this->messageManager->addSuccessMessage(__("Recalculation finished. Items updated: %1", $updated));
        } elseif ($alreadyCalculated > 0) {
            $this->messageManager->addNoticeMessage(__('%1 item(s) already have a calculated print price.', $alreadyCalculated));
        } else {
            $this->messageManager->addNoticeMessage(__('No eligible items found for recalculation from %1 onward.', self::MIN_RECALC_DATE));
        }

        return $this->_redirect('*/*/index', ['from' => $from, 'to' => $to]);
    }

    private function getEffectiveFromDate($from)
    {
        $minimumDate = self::MIN_RECALC_DATE;

        if (empty($from)) {
            return $minimumDate;
        }

        $fromTimestamp = strtotime($from);
        $minimumTimestamp = strtotime($minimumDate);

        if ($fromTimestamp && $minimumTimestamp && $fromTimestamp < $minimumTimestamp) {
            return $minimumDate;
        }

        return $from;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_PrintPriceTracking::report');
    }
}
