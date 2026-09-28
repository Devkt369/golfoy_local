<?php
namespace ArtM\InvoiceGrid\Model\Order\Pdf\Total;

class DefaultTotal extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
{
    public function getTotalsForDisplay()
    {
        $amount = '';
        $title = __($this->getTitle());
        if ($this->getTitleSourceField()) {
            if($title == 'Discount'){
                $label = $title . ' (' . $this->getTitleDescription() . '):'; 
                $amount = $this->getOrder()->getData('base_discount_amount') + $this->getOrder()->getData('gift_voucher_discount');
                if($amount == 0){
                    return [];
                }else{
                    $amount = $this->getOrder()->formatPriceTxt($amount);
                }
            }
            else{
                $label = $title . ' (' . $this->getTitleDescription() . '):';   
            }
        } else {
            $label = $title . ':';
        }

        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        $total = ['amount' => $amount, 'label' => $label, 'font_size' => $fontSize];
        return [$total];
    }
}