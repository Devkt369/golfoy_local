<?php
namespace ArtM\CustomProductDesign\Model\Order\Pdf\Items\Invoice;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Pdf\Items\Invoice\DefaultInvoice as BaseDefaultInvoice;
use Magento\Sales\Model\RtlTextHandler;

/**
 * Sales Order Invoice Pdf default items renderer
 */
class CustomInvoice extends BaseDefaultInvoice
{   
        /**
     * Core string
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @var RtlTextHandler
     */
    private $rtlTextHandler;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param RtlTextHandler|null $rtlTextHandler
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\StringUtils $string,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        ?RtlTextHandler $rtlTextHandler = null
    ) {
        $this->string = $string;
        parent::__construct(
            $context,
            $registry,
            $taxData,
            $filesystem,
            $filterManager,
            $string,
            $resource,
            $resourceCollection,
            $data,
            $rtlTextHandler
        );
    }
    /**
     * Prepare text for display
     *
     * @param string $text
     * @return string
     */
    public function prepareText($text)
    {
        return htmlspecialchars($text, ENT_QUOTES);
    }

    /**
     * Draw item line
     *
     * @return void
     */
    public function draw()
    {
        $order = $this->getOrder();
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $lines = [];

        // draw Product name
        $lines[0][] = [
            'text' => $this->string->split($this->prepareText((string)$item->getName()), 35, true, true),
            'feed' => 35
        ];

        // draw SKU
        $lines[0][] = [
            'text' => $this->string->split($this->prepareText((string)$this->getSku($item)), 25),
            'feed' => 310,
            'align' => 'right',
        ];

        // draw QTY
        $lines[0][] = ['text' => $item->getQty() * 1, 'feed' => 430, 'align' => 'right'];
        $rowTotal = (float) $item->getRowTotal();
        $rewardDiscount = abs((float) $item->getData('mp_reward_discount'));
        $taxCompensation = (float) $item->getDiscountTaxCompensationAmount();
        $couponDiscount = abs((float) $item->getDiscountAmount());

        $orderItemId = $item->getOrderItemId();
        $orderItem = $order->getItemById($orderItemId);
        $giftVoucher = $orderItem->getData('gift_voucher_discount');

        $taxableAmount = $rowTotal + $taxCompensation - $couponDiscount - $rewardDiscount;
        if ($taxableAmount < 0) {
            $taxableAmount = $rowTotal;
        }




        $baseRow  = (float)$item->getBaseRowTotal();
        $discount = abs((float)$item->getMpRewardBaseDiscount()); // 1000
        $taxComp  = (float)$item->getDiscountTaxCompensationAmount(); // 47.62
        $couponDiscount = (float)$item->getDiscountAmount();  // 1,310.5

        $orderItemId    = $item->getOrderItemId();
        $orderItem      = $order->getItemById($orderItemId);
        $giftVoucher    = $orderItem->getData('gift_voucher_discount');

        $taxableAmount = ($baseRow  + $taxComp) - abs(($couponDiscount  - (float)$giftVoucher)) - $discount ; // 3,032.85
        
        if ($taxableAmount < 0) {
            $taxableAmount = $item->getBaseRowTotal();
        }
        $i = 0;
        $prices = $this->getItemPricesForDisplay();
        $feedPrice = 410;
        $feedSubtotal = 565;
        foreach ($prices as $priceData) {
            if (isset($priceData['label'])) {
                $lines[$i][] = ['text' => $priceData['label'], 'feed' => $feedPrice, 'align' => 'right'];
                $lines[$i][] = ['text' => $priceData['label'], 'feed' => $feedSubtotal, 'align' => 'right'];
                $i++;
            }
            
            $lines[$i][] = [
                'text' => $priceData['price'],
                'feed' => 380,
                'font' => 'bold',
                'align' => 'right',
            ];

            $lines[$i][] = [
                'text' => $order->formatPriceTxt($rowTotal),
                'feed' => $feedSubtotal,
                'font' => 'bold',
                'align' => 'right',
            ];
            $lines[$i+1][] = [
                'text' => "Taxable Amount: " . $order->formatPriceTxt($taxableAmount),
                'feed' => $feedSubtotal,
                'font' => 'bold',
                'align' => 'right',
            ];
            $i++;
        }

        $taxPercent = (int)$orderItem->getData("tax_percent");

        $lines[0][] = [
            'text' => [
                $order->formatPriceTxt($item->getTaxAmount()),  // line 1
                "({$taxPercent}%)"                  // line 2
            ],
            'feed'  => 490,
            'font'  => 'bold',
            'align' => 'right',
        ];

        
        // custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                
                // draw options label
                $lines[][] = [
                    'text' => $this->string->split($this->filterManager->stripTags($option['label']), 40, true, true),
                    'font' => 'italic',
                    'feed' => 35,
                ];

                // Checking whether option value is not null
                if ($option['value'] !== null) {
                    if (isset($option['print_value'])) {
                        $printValue = $option['print_value'];
                    } else {
                        $printValue = $this->filterManager->stripTags($option['value']);
                    }

                    $values = explode(', ', $printValue);
                    foreach ($values as $value) {
                        if (strpos($value, 'session_id') !== false || strpos($value, 'product_id') !== false) {
                            $toshow = 'Image Uploaded'; 
                            $lines[][] = ['text' => $this->string->split($toshow, 30, true, true), 'font' => 'bold', 'feed' => 40];
                        } elseif (strpos($value, 'customprint/') !== false || strpos($value, 'golfoy.com') !== false) {
                            $toshow = 'Image Uploaded'; 
                            $lines[][] = ['text' => $this->string->split($toshow, 30, true, true), 'font' => 'bold', 'feed' => 40];
                        }else {
                            $lines[][] = ['text' => $this->string->split($value, 30, true, true), 'font' => 'bold', 'feed' => 40];
                        }
                    }
                }
            }
        }
        $lineBlock = ['lines' => $lines, 'height' => 20];

        $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $this->setPage($page);
    }
}
