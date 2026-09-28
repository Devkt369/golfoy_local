<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Giftvoucher\Model\Pdf\Invoice;

/**
 * Giftvoucher Pdf Invoice Item Model
 *
 * @module   Giftvoucher
 * @author   Magestore Developer
 */
class Item extends \Magento\Sales\Model\Order\Pdf\Items\AbstractItems
{
    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @var \Magestore\Giftvoucher\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magestore\Giftvoucher\Model\Giftvoucher
     */
    protected $_giftvoucher;

    /**
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $_currencyFactory;

    /**
     * Item constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Framework\Stdlib\StringUtils $coreString
     * @param \Magestore\Giftvoucher\Helper\Data $helper
     * @param \Magestore\Giftvoucher\Model\Giftvoucher $giftvoucher
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\StringUtils $coreString,
        \Magestore\Giftvoucher\Helper\Data $helper,
        \Magestore\Giftvoucher\Model\Giftvoucher $giftvoucher,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->string = $coreString;
        $this->_helper = $helper;
        $this->_giftvoucher = $giftvoucher;
        $this->_currencyFactory = $currencyFactory;
        parent::__construct(
            $context,
            $registry,
            $taxData,
            $filesystem,
            $filterManager,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @inheritDoc
     */
public function draw()
{
    $order = $this->getOrder();
    $item  = $this->getItem();
    $pdf   = $this->getPdf();
    $page  = $this->getPage();
    $lines = [];

    $columns = method_exists($pdf, 'getInvoiceTableColumns') ? $pdf->getInvoiceTableColumns() : [
        'sl_left' => 25,
        'sl_right' => 43,
        'description_left' => 43,
        'description_right' => 280,
        'hsn_left' => 280,
        'hsn_right' => 340,
        'qty_left' => 340,
        'qty_right' => 400,
        'rate_left' => 400,
        'rate_right' => 460,
        'unit_left' => 460,
        'unit_right' => 488,
        'amount_left' => 488,
        'amount_right' => 570,
        'disc_left' => 430,
        'disc_right' => 488
    ];

    // Basic values
    $qty = (float)$item->getQty();
    $price = (float)$item->getPrice();
    $rowTotal = (float)$item->getRowTotal();

    $qtyText    = (string)$qty;
    $rateText   = number_format($price, 2);
    $amountText = number_format($rowTotal, 2);

    // Serial number (optional)
    $serialNumber = method_exists($pdf, 'getNextInvoiceItemNumber')
        ? $pdf->getNextInvoiceItemNumber()
        : '';

    // Product Name
    $lines[0][] = [
        'text' => (string)$serialNumber,
        'feed' => $columns['sl_left'],
        'width' => $columns['sl_right'] - $columns['sl_left'],
        'align' => 'center',
        'font_size' => 10,
    ];

    $lines[0][] = [
        'text' => $this->string->split($this->prepareText((string)$item->getName()), 34, true, true),
        'feed' => $columns['description_left'] + 3,
        'font' => 'bold',
        'font_size' => 7,
    ];

    // HSN (not applicable → dash)
    $lines[0][] = [
        'text' => '-',
        'feed' => $columns['hsn_left'],
        'width' => $columns['hsn_right'] - $columns['hsn_left'],
        'align' => 'center',
        'font_size' => 8,
    ];

    // Qty
    $lines[0][] = [
        'text' => $qtyText,
        'feed' => $columns['qty_left'],
        'width' => $columns['qty_right'] - $columns['qty_left'],
        'align' => 'center',
        'font_size' => 8,
    ];

    // Rate
    $lines[0][] = [
        'text' => $order->formatPriceTxt($rateText),
        'feed' => $columns['rate_right'] - 4,
        'align' => 'right',
        'font_size' => 8,
    ];

    // Unit
    $lines[0][] = [
        'text' => 'Pcs',
        'feed' => $columns['unit_left'],
        'width' => $columns['unit_right'] - $columns['unit_left'],
        'align' => 'center',
        'font_size' => 8,
    ];

    // Discount (Gift vouchers usually 0)
    $lines[0][] = [
        'text' => $order->formatPriceTxt(0),
        'feed' => $columns['disc_left'],
        'width' => $columns['disc_right'] - $columns['disc_left'],
        'align' => 'right',
        'font_size' => 8,
    ];

    // Amount
    $lines[0][] = [
        'text' => $order->formatPriceTxt($amountText),
        'feed' => $columns['amount_right'] - 6,
        'align' => 'right',
        'font' => 'bold',
        'font_size' => 8,
    ];

    // OPTIONS (same as your custom logic)
    $options = $this->getItemOptions();
    if ($options) {
        if ($options) {
            foreach ($options as $option) {
                $label = (string)$option['label'];

                if ($label === 'Gift Card Code') {
                    $lines[][] = [
                        'text' => $this->string->split($option['print_value'], 30, true, true),
                        'feed' => $columns['description_left'] + 8,
                        'font_size' => 8
                    ];
                }
            }
        }
    }

    $lineBlock = ['lines' => $lines, 'height' => 12];
    $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
    $this->setPage($page);
}

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getItemOptions()
    {
        $result = parent::getItemOptions();
        $item = $this->getItem()->getOrderItem();
        if ($item->getProductType() != 'giftvoucher') {
            return $result;
        }
        if ($options = $item->getProductOptionByCode('info_buyRequest')) {
            foreach ($this->_helper->getGiftVoucherOptions() as $code => $label) {
                if (isset($options[$code]) && $options[$code]) {
                    $result[] = [
                        'label' => $label,
                        'value' => $options[$code],
                        'print_value' => $options[$code],
                    ];
                }
            }
        }
        if ($item->getQuoteItemId()) {
            $giftVouchers = $this->_giftvoucher->getCollection()->addItemFilter($item->getQuoteItemId());
        } else {
            $giftVouchers = $this->_giftvoucher->getCollection()->addItemFilter($item->getId(), true);
        }
        if ($giftVouchers->getSize()) {
            $giftVouchersCode = [];
            foreach ($giftVouchers as $giftVoucher) {
                $currency = $this->_currencyFactory->create()->load($giftVoucher->getCurrency());
                $balance = $giftVoucher->getBalance();
                if ($currency) {
                    $balance = $currency->format($balance, [], false);
                }
                $giftVouchersCode[] = $giftVoucher->getGiftCode() . ' (' . $balance . ') ';
            }
            $codes = implode(' ', $giftVouchersCode);
            $result[] = [
                'label' => __('Gift Card Code'),
                'value' => $codes,
                'print_value' => $codes,
            ];
        }

        return $result;
    }
    public function prepareText($text)
    {
        return htmlspecialchars($text, ENT_QUOTES);
    }

}
