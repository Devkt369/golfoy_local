<?php
namespace ArtM\Common\Pdf;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\RtlTextHandler;

class CustomShipmentPdf extends \Magento\Sales\Model\Order\Pdf\Shipment
{
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;
    
    /**
     * @var RtlTextHandler
     */
    protected $rtlTextHandler;

    /**
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Sales\Model\Order\Pdf\Config $pdfConfig
     * @param \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory
     * @param \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param array $data
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sales\Model\Order\Pdf\Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $appEmulation,
        array $data = []
    ) {
        $this->appEmulation = $appEmulation;
        // Using ObjectManager directly is not recommended, but keeping it as in the original code.
        $this->rtlTextHandler = ObjectManager::getInstance()->get(\Magento\Sales\Model\RtlTextHandler::class);
        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $storeManager,
            $appEmulation,
            $data
        );
    }

    /**
     * Insert title and number for concrete document type
     *
     * @param  \Zend_Pdf_Page $page
     * @param  string $text
     * @return void
     */
    public function insertDocumentNumber(\Zend_Pdf_Page $page, $text)
    {
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 10);
        $docHeader = $this->getDocHeaderCoordinates();
        $page->drawText($text, 35, $docHeader[1] - 15, 'UTF-8');
    }

    /**
     * Return PDF document
     *
     * @param \Magento\Sales\Model\Order\Shipment[] $shipments
     * @return \Zend_Pdf
     */
    public function getPdf($shipments = [])
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('shipment');

        $pdf = new \Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new \Zend_Pdf_Style();
        $this->_setFontBold($style, 10);
        
        foreach ($shipments as $shipment) {
            if ($shipment->getStoreId()) {
                $this->appEmulation->startEnvironmentEmulation(
                    $shipment->getStoreId(),
                    \Magento\Framework\App\Area::AREA_FRONTEND,
                    true
                );
                $this->_storeManager->setCurrentStore($shipment->getStoreId());
            }
            $page = $this->newPage();
            $order = $shipment->getOrder();
            
            /* Add image */
            $this->insertLogo($page, $shipment->getStore());
            
            /* Add address */
            $this->insertAddress($page, $shipment->getStore());
            
            /* Add head */
            $this->insertOrder(
                $page,
                $shipment,
                $this->_scopeConfig->isSetFlag(
                    self::XML_PATH_SALES_PDF_SHIPMENT_PUT_ORDER_ID,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $order->getStoreId()
                )
            );
            
            /* Add document text and number */
            $this->insertDocumentNumber($page, __('Packing Slip # ') . $shipment->getIncrementId());
            
            /* Add table */
            $this->_drawHeader($page);
            
            /* Add body */
            foreach ($shipment->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }
            
            /* Add totals */
            $this->insertTotals($page, $shipment);

            if ($shipment->getStoreId()) {
                $this->appEmulation->stopEnvironmentEmulation();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }
    
    /**
     * Draw header for item table
     *
     * @param \Zend_Pdf_Page $page
     * @return void
     */
    protected function _drawHeader(\Zend_Pdf_Page $page)
    {
        $this->_setFontRegular($page, 10);
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 25);
        $this->y -= 15;

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));

        //columns headers
        $lines[0][] = ['text' => __('Qty'), 'feed' => 45, 'align' => 'right'];
        $lines[0][] = ['text' => __('Products'), 'feed' => 65, 'align' => 'left'];
        $lines[0][] = ['text' => __('SKU'), 'feed' => 255, 'align' => 'left'];
        $lines[0][] = ['text' => __('Price'), 'feed' => 375, 'align' => 'right'];
        $lines[0][] = ['text' => __('Tax'), 'feed' => 445, 'align' => 'right'];
        $lines[0][] = ['text' => __('Subtotal'), 'feed' => 545, 'align' => 'right'];

        $lineBlock = ['lines' => $lines, 'height' => 10];
        $this->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }


    protected function insertOrder(&$page, $obj, $putOrderId = true)
    {
        // This method is kept as in the original to preserve the layout for address and shipping info.
        // It has been slightly adjusted to call the correct insertTotals method.
        // The original logic seems to be a mix of invoice and shipment PDF, which is preserved here.
        
        $nullShipmentForMobileNumber = true;
        if ($obj instanceof \Magento\Sales\Model\Order) {
            $shipment = null;
            $order = $obj;
        } elseif ($obj instanceof \Magento\Sales\Model\Order\Shipment) {
            $shipment = $obj;
            $order = $shipment->getOrder();
            $nullShipmentForMobileNumber = false;
        }

        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.45));
        $page->drawRectangle(25, $top, 570, $top - 55);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->setDocHeaderCoordinates([25, $top, 570, $top - 55]);
        $this->_setFontRegular($page, 10);

        if ($putOrderId) {
            $page->drawText(__('Order # ') . $order->getRealOrderId(), 35, $top -= 30, 'UTF-8');
            $top +=15;
        }

        $top -=30;
        $page->drawText(
            __('Order Date: ') .
            $this->_localeDate->formatDate(
                $this->_localeDate->scopeDate(
                    $order->getStore(),
                    $order->getCreatedAt(),
                    true
                ),
                \IntlDateFormatter::MEDIUM,
                false
            ),
            35,
            $top,
            'UTF-8'
        );

        $top -= 10;
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $top, 275, $top - 25);
        $page->drawRectangle(275, $top, 570, $top - 25);

        /* Billing Address */
        $billingAddress = $this->_formatAddress($this->addressRenderer->format($order->getBillingAddress(), 'pdf'));

        /* Payment */
        $paymentInfo = $this->_paymentData->getInfoBlock($order->getPayment())->setIsSecureMode(true)->toPdf();
        $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
        foreach ($payment as $key => $value) {
            if (strip_tags(trim($value)) == '') {
                unset($payment[$key]);
            }
        }
        reset($payment);

        /* Shipping Address and Method */
        if (!$order->getIsVirtual()) {
            /* Shipping Address */
            $shippingAddress = $this->_formatAddress(
                $this->addressRenderer->format($order->getShippingAddress(), 'pdf')
            );
            $shippingMethod = $order->getShippingDescription();
        }

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 12);

        if (!$order->getIsVirtual()) {
            // For a shipment/packing slip, "Ship to" is primary.
            $page->drawText(__('Ship to:'), 35, $top - 15, 'UTF-8');
            // $page->drawText(__('Sold to:'), 285, $top - 15, 'UTF-8');
        } else {
            // Virtual order fallback
            $page->drawText(__('Sold to:'), 35, $top - 15, 'UTF-8');
            $page->drawText(__('Payment Method:'), 285, $top - 15, 'UTF-8');
        }

        $addressesHeight = $this->_calcAddressHeight($billingAddress);
        if (isset($shippingAddress)) {
            $addressesHeight = max($addressesHeight, $this->_calcAddressHeight($shippingAddress));
        }

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, $top - 25, 570, $top - 33 - $addressesHeight);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 10);
        
        $this->y = $top - 40;
        $addressesStartY = $this->y;

        // Shipping Address on the left for Packing Slip
        if (!$order->getIsVirtual()) {
            foreach ($shippingAddress as $value) {
                if ($value !== '') {
                    $text = [];
                    foreach ($this->string->split($value, 45, true, true) as $_value) {
                        $text[] = $this->rtlTextHandler->reverseRtlText($_value);
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)), 35, $this->y, 'UTF-8');
                        $this->y -= 15;
                    }
                }
            }
        }
        
        $addressesEndY = $this->y;
        
        // Billing Address on the right
        // $this->y = $addressesStartY;
        // foreach ($billingAddress as $value) {
        //     if ($value !== '') {
        //         $text = [];
        //         foreach ($this->string->split($value, 45, true, true) as $_value) {
        //             $text[] = $this->rtlTextHandler->reverseRtlText($_value);
        //         }
        //         foreach ($text as $part) {
        //             $page->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
        //             $this->y -= 15;
        //         }
        //     }
        // }
        
        // $addressesEndY = min($addressesEndY, $this->y);
        // $this->y = $addressesEndY;
        
        // Bottom section for Payment and Shipping methods
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 275, $this->y - 25);
        $page->drawRectangle(275, $this->y, 570, $this->y - 25);

        $this->y -= 15;
        $this->_setFontBold($page, 12);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $page->drawText(__('Payment Method:'), 35, $this->y, 'UTF-8');
        $page->drawText(__('Shipping Method:'), 285, $this->y, 'UTF-8');

        $this->y -= 10;
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        
        $this->_setFontRegular($page, 10);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));

        $paymentLeft = 35;
        $yPayments = $this->y - 15;

        foreach ($payment as $value) {
            if (trim($value) != '') {
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach ($this->string->split($value, 45, true, true) as $_value) {
                    $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
                    $yPayments -= 15;
                }
            }
        }
        
        // Shipping method details
        $yShipments = $this->y - 53;
        // Show shipping method above tracking table
        if (!empty($shippingMethod)) {
            $this->_setFontBold($page, 10);
            $page->drawText(__('') . $shippingMethod, 285, $yShipments + 40, 'UTF-8');
            $yShipments -= 10; // Add more space before table
        }

        // Draw table header
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->setLineWidth(0.5);
        $page->drawRectangle(285, $yShipments + 15, 570, $yShipments);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 10);
        
        // Add background grey for the header row
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->drawRectangle(285, $yShipments + 15, 570, $yShipments);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $page->drawText(__('Title'), 290, $yShipments + 5, 'UTF-8');
        $page->drawText(__('Number'), 410, $yShipments + 5, 'UTF-8');

        $yShipments -= 15;
        $this->_setFontRegular($page, 10);
        if (isset($shipment) && $shipment) {
            foreach ($shipment->getAllTracks() as $track) {
                $title = $track->getTitle() ?? $order->getShippingDescription();
                $number = $track->getNumber();
                $page->drawText($title, 290, $yShipments, 'UTF-8');
                $page->drawText($number, 410, $yShipments, 'UTF-8');
                $yShipments -= 15;
            }
        }

        $currentY = min($yPayments, $yShipments);
        
        $page->drawLine(25, $this->y + 10, 25, $currentY);
        $page->drawLine(275, $this->y + 10, 275, $currentY);
        $page->drawLine(570, $this->y + 10, 570, $currentY);
        $page->drawLine(25, $currentY, 570, $currentY);
        
        $this->y = $currentY;
        $this->y -= 15;
    }

    /**
     * Insert order totals to pdf page
     *
     * @param \Zend_Pdf_Page $page
     * @param \Magento\Sales\Model\AbstractModel $source
     * @return \Zend_Pdf_Page
     */
    protected function insertTotals($page, $source)
    {
        $order = $source->getOrder();
        $lineBlock = ['lines' => [], 'height' => 15];

        // Subtotal (Excl. Tax)
        $lineBlock['lines'][] = [
            [
                'text' => __('Subtotal (Excl. Tax):'),
                'feed' => 475, 'align' => 'right', 'font_size' => 10, 'font' => 'regular',
            ],
            [
                'text' => $order->formatPriceTxt($order->getSubtotal()),
                'feed' => 565, 'align' => 'right', 'font_size' => 10, 'font' => 'regular',
            ]
        ];
        
        // Subtotal (Incl. Tax)
        $lineBlock['lines'][] = [
            [
                'text' => __('Subtotal (Incl. Tax):'),
                'feed' => 475, 'align' => 'right', 'font_size' => 10, 'font' => 'regular',
            ],
            [
                'text' => $order->formatPriceTxt($order->getSubtotalInclTax()),
                'feed' => 565, 'align' => 'right', 'font_size' => 10, 'font' => 'regular',
            ]
        ];

        // Discount
        if ((double)$order->getDiscountAmount() != 0) {
            $discountLabel = __('Discount');
            if ($order->getCouponCode()) {
                $discountLabel .= ' (' . $order->getCouponCode() . '):';
            } else {
                 $discountLabel .= ':';
            }
            $lineBlock['lines'][] = [
                [
                    'text' => $discountLabel,
                    'feed' => 475, 'align' => 'right', 'font_size' => 10, 'font' => 'regular',
                ],
                [
                    'text' => $order->formatPriceTxt($order->getDiscountAmount()),
                    'feed' => 565, 'align' => 'right', 'font_size' => 10, 'font' => 'regular',
                ]
            ];
        }

        // Grand Total (Excl. Tax)
        $grandTotalExclTax = $order->getGrandTotal() - $order->getTaxAmount();
        $lineBlock['lines'][] = [
            [
                'text' => __('Grand Total (Excl. Tax):'),
                'feed' => 475, 'align' => 'right', 'font_size' => 10, 'font' => 'regular',
            ],
            [
                'text' => $order->formatPriceTxt($grandTotalExclTax),
                'feed' => 565, 'align' => 'right', 'font_size' => 10, 'font' => 'regular',
            ]
        ];

        // Tax
        // Note: This assumes a single 18% GST rate as per the example. For multiple rates, this logic would need to be more complex.

        $invoice = $order->getInvoiceCollection()->getFirstItem();
        if (!$invoice || !$invoice->getId()) {
            return $page;
        }

        $taxGroups = [];

        foreach ($source->getAllItems() as $shipmentItem) {
            if ($shipmentItem->getQty() <= 0) continue;

            foreach ($invoice->getAllItems() as $invoiceItem) {
                if ($invoiceItem->getOrderItemId() != $shipmentItem->getOrderItemId()) {
                    continue;
                }

                $percent = (float)$invoiceItem->getOrderItem()->getTaxPercent();
                $taxAmt  = (float)$invoiceItem->getBaseTaxAmount();

                if ($percent > 0) {
                    $taxGroups[$percent] = ($taxGroups[$percent] ?? 0) + $taxAmt;
                }
            }
        }

        foreach ($taxGroups as $percent => $taxAmt) {
            $lineBlock['lines'][] = [
                [
                    'text' => "GST @{$percent}%",
                    'feed' => 475,
                    'align' => 'right',
                    'font_size' => 10,
                    'font' => 'regular',
                ],
                [
                    'text' => $order->formatPriceTxt($taxAmt),
                    'feed' => 565,
                    'align' => 'right',
                    'font_size' => 10,
                    'font' => 'regular',
                ]
            ];
        }

        // Total Tax
        $totalTax = array_sum($taxGroups);
        if ($totalTax > 0) {
            $lineBlock['lines'][] = [
                [
                    'text' => 'Tax:',
                    'feed' => 475,
                    'align' => 'right',
                    'font_size' => 10,
                    'font' => 'regular',
                ],
                [
                    'text' => $order->formatPriceTxt($totalTax),
                    'feed' => 565,
                    'align' => 'right',
                    'font_size' => 10,
                    'font' => 'regular',
                ]
            ];
        }

        // Grand Total (Incl. Tax)
        $lineBlock['lines'][] = [
            [
                'text' => __('Grand Total (Incl. Tax):'),
                'feed' => 475, 'align' => 'right', 'font_size' => 12, 'font' => 'bold',
            ],
            [
                'text' => $order->formatPriceTxt($order->getGrandTotal()),
                'feed' => 565, 'align' => 'right', 'font_size' => 12, 'font' => 'bold',
            ]
        ];

        $this->y -= 20;
        $page = $this->drawLineBlocks($page, [$lineBlock]);
        return $page;
    }
}