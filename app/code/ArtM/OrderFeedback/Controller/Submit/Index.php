<?php
namespace ArtM\OrderFeedback\Controller\Submit;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Escaper;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $_pageFactory;
    protected $resultFactory;
    protected $orderfeedback;
    protected $orderFactory;
    protected $timezone;
    protected $escaper;

    /**
     * Undocumented function
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $_pageFactory
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \ArtM\OrderFeedback\Model\MaintableFactory $orderfeedback
     * @param TimezoneInterface $timezone
     * @param Escaper $escaper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $_pageFactory,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        //\ArtM\OrderFeedback\Model\Maintable $orderfeedback
        \ArtM\OrderFeedback\Model\MaintableFactory $orderfeedback,
        TimezoneInterface $timezone,
        Escaper $escaper,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        $this->_pageFactory = $_pageFactory;
        $this->resultFactory = $resultFactory;
        $this->orderfeedback = $orderfeedback;
        $this->orderFactory = $orderFactory;
        $this->timezone = $timezone;
        $this->escaper = $escaper;
        return parent::__construct($context);

    }
    
    public function execute()
    {
        $rating = ($this->getRequest()->getParam('rating') == '') ? ' ' : $this->getRequest()->getParam('rating');
        $review = ($this->getRequest()->getParam('review') == '') ? ' ' : $this->getRequest()->getParam('review');
        $orderid = ($this->getRequest()->getParam('orderid') == '') ? ' ' : $this->getRequest()->getParam('orderid');
        $deliveryNote = $this->getRequest()->getParam('deliveryNote') === '' ? ' ' : $this->getRequest()->getParam('deliveryNote');
        $rating = $this->escaper->escapeHtml($rating);
        $deliveryNote = $this->escaper->escapeHtml($deliveryNote);
        $review = $this->escaper->escapeHtml($review);
        
        $orderfeedback_model = $this->orderfeedback->create();
        $currentTimestamp = date('Y-m-d H:i:s');
        $utcTimeZone = new \DateTimeZone('UTC');
        $dateTimeUTC = new \DateTime($currentTimestamp, $utcTimeZone);
        $targetTimeZone = $this->timezone->getConfigTimezone('default');
        $dateTimeUTC->setTimezone(new \DateTimeZone($targetTimeZone));
        $indianTime = $dateTimeUTC->format('Y-m-d H:i:s');
       
        $orderfeedback_collection = $orderfeedback_model->getCollection()->addFieldToFilter('order_id', $orderid);
        
        if($this->getRequest()->getParam('deliveryNote')) {
            if( $orderfeedback_collection->count() == 0 ){
                $orderfeedback_model->addData([
                    "order_id" => $orderid,
                    "delivery_notes" => $deliveryNote
                ]);
                $orderfeedback_model->save();
            }else{
                foreach ($orderfeedback_collection as $orderFeedback) {
                    $orderFeedback->setData('delivery_notes', $deliveryNote);
                    $orderFeedback->save();
                }
            }
        }else{

            if( $orderfeedback_collection->count() == 0 ){
                $orderfeedback_model->addData([
                    "order_id" => $orderid,
                    "rating" => $rating,
                    "review" => $review,
                    "updated_at" => $indianTime
                ]);
                $orderfeedback_model->save();
                    
                $order = $this->orderFactory->create()->loadByIncrementId($orderid);
                    
                $orderfeedback = "(".$rating.") - ".$review;
                    
                $order->setData('orderfeedback' , $orderfeedback );
                if( $order->save() ){
                    return true;   
                }
            }else{
                foreach ($orderfeedback_collection as $orderFeedback) {
                    $orderFeedback->setData('rating', $rating);
                    $orderFeedback->setData('review', $review);
                    $orderFeedback->save();
                }
            }
        }
        return true;
    }
}


//rating=3&review=review_test&orderid=1000072101