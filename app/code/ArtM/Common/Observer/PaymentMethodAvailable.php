<?php

namespace ArtM\Common\Observer;

use Magento\Framework\Event\ObserverInterface;
use ArtM\Common\Helper\Data;

class PaymentMethodAvailable implements ObserverInterface
{
    protected $Cart;
    protected $helperData;
    /**
     * payment_method_is_active event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Cart $Cart,
        Data $helperData
    )
    {
        $this->Cart = $Cart;
        $this->helperData = $helperData;
    }
    
        
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
         $itemsCollection = $this->Cart->getQuote()->getItemsCollection();
        $v = $this->Cart->getQuote()->getAllVisibleItems();

        $p = $this->Cart->getItems();

        $a= $this->Cart->getQuote()->getAllItems();
        $codoption = 1;
        foreach($v as $item) {
            $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
            $noneCodProduct = $this->helperData->getConfig('common/disablecashondelivery/nonecodproudct');
            if($noneCodProduct){
                $noneCodProduct = str_replace(' ', '', $noneCodProduct);
                $noneCodProduct = explode(',', $noneCodProduct);
                foreach($noneCodProduct as $itemId){
                    if($itemId == $item->getProduct()->getEntityId()){
                        $codoption = 0;
                        break;
                    }
                }
            }
            if($codoption == 0){
                break;
            }
            $arcod = array();
            $tarry=0;
            $larrys = count($options);
            $attributeSetId =  $item->getProduct()->getAttributeSetId();
            foreach ($options as $val) 
                {

                        if(is_array($val)){

                        foreach($val as $deepval)
                        {


                            if(is_array($deepval))
                            {  
                                if(array_key_exists('print_value', $deepval)){
                                    if($attributeSetId != '81'){
                                        $codoption = 0;
                                        break;
                                    }
                                }
                            }
                        }

                    }

                    $tarry = $tarry+1;
                }
                if($item->getProduct()->getTypeId() == 'giftvoucher')
                $codoption = 0;
                
        }
        if ($codoption==0){
            if($observer->getEvent()->getMethodInstance()->getCode()=="cashondelivery"){
                $checkResult = $observer->getEvent()->getResult();
                $checkResult->setData('status', "sendoncod");
                $checkResult->setData('is_available', false);

            }
        }
    }
}