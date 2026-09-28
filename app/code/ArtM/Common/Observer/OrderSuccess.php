<?php
namespace ArtM\Common\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderSuccess implements ObserverInterface
{
    protected $orderFactory;
    protected $invoiceService;
    protected $transactionFactory;
    protected $orderRepository;
    protected $_giftCardLogger;
    
    public function __construct(
        \ArtM\Common\Logger\GiftCardLogger $_giftCardLogger,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory
    ) {
        $this->_giftCardLogger = $_giftCardLogger;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $this->setOrderStatusCod($order->getId());

        if( $order->getPayment()->getMethod() == 'free'){
            if ($order->getState() == 'new') {
                $order->setState('processing');
            }
    
            if ($order->getStatus() == 'pending') {
                $order->setStatus('processing');
            }
        }
        $order->save();
        $items = $order->getAllItems();
        
        foreach ($items as $item) {
            $product = $item->getProduct();
            
            if ($product->getTypeId() == 'giftvoucher') {
                $giftCardItems[$item->getId()] = $item->getQtyOrdered();
                $this->createInvoice($order , $giftCardItems);
            }
        }
    }

    /**
     * Create invoice
     *
     * @param array $order
     * @param array $giftCardItems
     * @return void
     */
    public function createInvoice($order, $giftCardItems)
    {
        $this->_giftCardLogger->info("Creating gift card invoice");
        $this->_giftCardLogger->info("Order id " . $order->getId());
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order, $giftCardItems);
            $invoice->register();
            $invoice->save();
            $transaction = $this->transactionFactory->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transaction->save();
        }
    }

    /**
     * set order status cod
     *
     * @param int $orderId
     * @return void
     */
    public function setOrderStatusCod($orderId){
        if ($orderId) {
            $order = $this->orderRepository->get($orderId);

            if ($order && $order->getPayment()->getMethod() === 'cashondelivery') {
                $order->setStatus('sendoncod'); 
                $this->orderRepository->save($order);
            }
        }
    }
}
