<?php
namespace ArtM\CustomProductDesign\Model\Order\Pdf\Items\Shipment;

use Magento\Sales\Model\Order\Pdf\Items\Shipment\DefaultShipment as BaseDefaultShipment;
// use Magento\Framework\Stdlib\StringUtils;
// use Magento\Framework\Filter\FilterManager;

class CustomShipment extends BaseDefaultShipment
{
    /**
     * Core string
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
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
        array $data = []
    ) {
        // $this->string = $string;
        parent::__construct(
            $context,
            $registry,
            $taxData,
            $filesystem,
            $filterManager,
            $string,
            $resource,
            $resourceCollection,
            $data
        );
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
            'text' => $this->string->split($this->filterManager->stripTags($item->getName()), 35, true, true),
            'feed' => 50,
        ];

        // draw SKU
        $lines[0][] = [
            'text' => $this->string->split($item->getSku(), 35, true, true),
            'feed' => 250,
        ];

        // draw QTY
        $lines[0][] = [
            'text' => $item->getQty() * 1,
            'feed' => 35,
        ];

        $itemId = $item->getOrderItemId();
        $rowtax = 0;
        $rowtotal = 0;
        foreach ($order->getAllVisibleItems() as $oitem) {
            if($oitem->getId() == $itemId){                
               //$rowtotal = $oitem->getRowTotal()-$oitem->getDiscountAmount()+$oitem->getTaxAmount()+$oitem->getDiscountTaxCompensationAmount();            
                 // $rowtotal =  $oitem->getRowTotalInclTax();
                 $rowtotal =  $oitem->getRowTotal();
                $rowtax   =  $oitem->getTaxAmount();
            }
        } 
        // draw Price, tax, subtotal
        $lines[0][] = [
            'text' => $order->formatPriceTxt($item->getPrice()),
            'feed' => 410,
            'font' => 'bold',
            'align' => 'right',
        ];
        $lines[0][] = [
            'text' => $order->formatPriceTxt($rowtax),
            'feed' => 480,
            'font' => 'bold',
            'align' => 'right',
        ];
       
       
        $lines[0][] = [
            'text' => $order->formatPriceTxt($rowtotal),
            'feed' => 565,
            'font' => 'bold',
            'align' => 'right',
        ];

        // Custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = [
                    'text' => $this->string->split($this->filterManager->stripTags($option['label']), 70, true, true),
                    'font' => 'italic',
                    'feed' => 60,
                ];

                // draw options value
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
