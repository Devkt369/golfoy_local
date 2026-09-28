<?php

namespace ArtM\Review\Cron;

class CronJob
{
    public $arrrayOrders;
	public function __construct(
		
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Sales\Model\Order $orderinstance,
		\Magento\Customer\Model\Customer $customerModel,
		\ArtM\Review\Model\CronFactory $reviewCronFactory,
		\ArtM\Review\Helper\Data $helper,
		\Magento\Catalog\Helper\Image $imageHelper,
		\Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
		\Magento\Catalog\Model\Product $productModel,
		\Magento\Framework\UrlInterface $url

	){
		$this->storeManager     = $storeManager;
		$this->orderinstance    = $orderinstance;
		$this->customerModel    = $customerModel;
		$this->reviewCronModel  = $reviewCronFactory->create();
		$this->helper           = $helper;
		$this->imageHelper      = $imageHelper;
		$this->transportBuilder = $transportBuilder;
		$this->productModel     = $productModel;
		$this->url              = $url;
		$this->arrrayOrders     = [];
		

	}
	private function saveOrder($order){	
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);
		$logger->info("order ".$order['increment_id']);
		$date = date('Y-m-d H:i:s');
		$this->reviewCronModel->load($order['increment_id'], 'orderid');
		if(!$this->reviewCronModel->getId()) {
			$data = ['orderid'        => $order['increment_id'],
					'prdids'          => $order['prd_ids'],
					'hashid'          => $order['hashid'],
					'customerid'      => $order['customer_id'],
					'customeremail'   => $order['customer_email'], 
					'customername'    => $order['customer_firstname'].' '.$order['customer_lastname'], 
					'reviewed'        => 'no',
					'clicked'         => 'no', 
					'sent_at'         => '0000-00-00 00:00:00', 
					'created_at'      => $date,
					'order_source'    => 'website',
					'mail_status'     => '',
					'order_date'      => $order['created_at']
					];
	
			$this->reviewCronModel->setData($data)->save();
			$this->reviewCronModel->unsetData();
			$this->sendEmail($order);
			
		}
	}
	private function sendEmail($order){
        $storeId    = $this->storeManager->getStore()->getStoreId();
		//$emailFrom  = $this->helper->getConfig('trans_email/ident_support/email');
		//$nameFrom   = $this->helper->getConfig('trans_email/ident_support/name');

		$emailFrom  = "feedback@golfoy.com";
		$nameFrom   = "Golfoy Customer Care";
		$cName      = $order['customer_firstname'].' '.$order['customer_lastname'];
		$cFName = 'Dear '.$cName.',';
		$parray = explode(',',$order['prd_ids']);
			
			$rrows ="";
			foreach($parray as $pid){
				$_product = $this->productModel->load($pid);
				$name = $_product->getName();
				$opt = (isset($this->arrrayOrders[$order['increment_id']][$pid]))  ? $this->arrrayOrders[$order['increment_id']][$pid]  : '';    
				//$image = $this->imageHelper->init($_product, 'product_base_image')->getUrl();
				$imgurl =  $_product->getThumbnail();
				$image = $this->storeManager->getStore()->getBaseUrl().'media/catalog/product'.$imgurl ;
				$rrows .="<div style= 'font-family:Roboto;width:100%;max-width:100%;margin-bottom:10px;float:left;padding:15px;border:2px solid #f6f6f6;border-radius:5px;'>
				<div style='background:#f6f6f6;border-radius:5px;width:140px;float:left;'><img style='width:140px;max-width:140px;mix-blend-mode:multiply;'src= $image /> </div>
				<div style='padding-left:160px;'><span style='color:#000;font-size:16px;font-weight:600 !important;word-spacing: 2px;
				letter-spacing: .2px;display: inline-block;padding-bottom: 10px;'> $name </span> <br/> $opt </div>
				</div>";
				/*$rrows .="<div style= 'font-family:Roboto;width:100%;max-width:100%;margin-bottom:10px;float:left;padding:15px;border:2px solid #f6f6f6;border-radius:5px;display:flex;'>
                <div style='background:#f6f6f6;border-radius:5px;width:140px;float:left;'><img style='width: 1.4166in;max-width:140px;mix-blend-mode:multiply;'src= $image /> </div>
                <div style='padding-left:50px;'><span style='color:#000;font-size:16px;font-weight:600 !important;word-spacing: 2px;
                letter-spacing: .2px;display: inline-block;padding-bottom: 10px;'> $name </span> <br/> $opt </div>
                </div>";*/
			} 
			
			$OrderreviewUrl = $this->url->getUrl('/', ['_query' => ['hash'=> $order['hashid']]]);
			$OrderreviewUrlhtml = '<center> <a href="'.$OrderreviewUrl.'" ><img style= "width:175px;" src ="https://cdn.golfoy.com/pub/media/custom_mails/review_BTN.png" /></a></center><br/><br/>';   
		
			
			$content =  '<tr class="email-intro">
            <td>
            <p class="greeting" style="font-size:13px;">'.$cFName.'</p>
            <p style="font-size:13px;">
            You have recently made a purchase order #'.$order["increment_id"].' from Golfoy.com. Please help us improve our customer satisfaction by writing a quick review. we would be extremely grateful.
            </p>            
				</td>
			</tr>
			<tr><td>'.$rrows.'</td></tr>    
			<tr><td>'. $OrderreviewUrlhtml .'</td></tr>';


			$email  = $order['customer_email']; 
			$subj   = $cName.', Review your recent order at Golfoy.com';
			$templateVars = [
						'subj'  => $subj,
						'mail_body' => $content
					]; 
			$this->transportBuilder
				->setTemplateIdentifier(10)
				->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId])
				->setTemplateVars($templateVars)
				->setFrom(['name' => $nameFrom,'email' => $emailFrom])
				->addTo([$email])
				// ->addTo(['tech1@golfoy.com'])
                ->addBcc(['feedback@golfoy.com']);
			  
				$transport = $this->transportBuilder->getTransport();
				if (!isset($transport)) {
					$transport = $this->transportBuilder->getTransport();
				}    
			$transport->sendMessage();			
			
	}
	private function canSendEmail($orderObj){
		if($orderObj['pos_id'] != '' || $orderObj['customer_email'] == 'golfoyproshop.cgc@gmail.com' ||
			$orderObj['customer_email'] == 'golfoystudio@gmail.com' || $orderObj['customer_id'] == '' ||
			$orderObj['customer_firstname'] == ''	){ 
				return false;
			}
		return true;	
	}
	public function execute()
	{   
		return;
		$storeId    = $this->storeManager->getStore()->getStoreId();
		$baseurl    = $this->storeManager->getStore()->getBaseUrl();
		$fdays      = ($this->helper->getConfig('review/automated_emails/email_from_days')) ? $this->helper->getConfig('review/automated_emails/email_from_days') : "30";
		$tdays      = ($this->helper->getConfig('review/automated_emails/email_to_days')) ? $this->helper->getConfig('review/automated_emails/email_to_days') : "60";
				
		//get orders between specified days and prepare data
		$orders = $this->helper->getordersFromTo(-$tdays,-$fdays);
        if(count($orders) > 0){
			foreach($orders as $K => $order){   

				if(!$this->canSendEmail($order)){                 
                    unset($orders[$K]);
                    continue;
                }

                
				if($this->helper->checkIfAlreadyShown($order['increment_id'])){  
                    unset($orders[$K]);
				}
			}
	    }
       
		if(count($orders) > 0){
			foreach($orders as &$order){               
					$prd_ids = '';
					$orderObj = $this->orderinstance->load($order['entity_id']);
					$orderItems = $orderObj->getAllItems();        
					foreach ($orderItems as $item) {           
						if( $item->getParentItemId() == '' ){
							if($prd_ids == ''){
								$prd_ids = $item->getProductId();
							}else{
								$prd_ids = $prd_ids.",".$item->getProductId();
							}
							$options = $item->getProductOptions(); 
							$opthtml = '';
							if(isset($options['attributes_info'])){
								
								foreach($options['attributes_info'] as $option){
									
									$opthtml .=  '<span style="font-size:13px;display: inline-block;
									padding: 4px 0px 0px 0px;
									"><strong style="margin-right:7px";>'.$option['label'].':</strong> '.$option['value'].'</span><br/>';
								}
								
							}
							$this->arrrayOrders[$order['increment_id']][$item->getProductId()] = $opthtml;
						}
					}
					$date = date('Y-m-d H:i:s');
					$order['prd_ids'] = $prd_ids;
					$order['hashid']  = strtoupper(substr(hash('sha1', $order['increment_id'].strtotime($date)),0,10));
			}	
			
			foreach($orders as $orderr){ 
				$this->saveOrder($orderr);				
			}
		}

		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);
		$logger->info("Cron review working");
		return $this;
	}
}