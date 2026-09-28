<?php

namespace ArtM\Delhiveryqc\Controller\Adminhtml\Submit;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResponseInterface;
use ArtM\Delhiveryqc\Model\OrderQc;
use ArtM\Delhiveryqc\Model\ProductQc;

class Index extends Action
{
	private $pageFactory;
	private $OrderData;
	private $ProductData;
	private $helper;

	public function __construct(
		PageFactory $pageFactory, 
		Action\Context $context,
		\Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
		OrderQc $OrderData,
        \ArtM\Delhiveryqc\Helper\Data $helper,
		ProductQc $ProductData
		)
	{
		$this->pageFactory= $pageFactory;
		parent::__construct($context);
		$this->OrderData = $OrderData;
		$this->helper = $helper;
		$this->ProductData = $ProductData;
	}

	public function execute()
	{
		$OrderModel = $this->OrderData;
		$ProductModel = $this->ProductData;
		
		$request = $this->getRequest();
        $formdata = $request->getPostValue();
		
		$pdata = $formdata['product'];
        
        $data = $this->helper->CreateQcOrder($formdata);
        
        $response = $data['response'];
        $rawData = $data['rawData'];
        
        $responseData = json_decode($response, true);
        $packages = $responseData['packages'];
        $i=0; 
        $waybill = '';
        foreach($packages as $package){
            if( $i > 0 ){ $waybill = $waybill.","; }
            $waybill = $waybill.$package['waybill'];
            $i++;
        }
//        echo "waybill: ".$waybill."<br>";
        date_default_timezone_set("Asia/Calcutta");   //India time (GMT+5:30)
        $date = date('Y-m-d H:i:s' );
        
        
		$OrderModel->setData([
			"order" => $formdata['incrementid'],
			"qc_id" => $waybill,
			"phone" => $formdata['telephone'],
			"products_desc" => $formdata['product_description'],
			"name" => preg_replace('/[^A-Za-z0-9s]/', '', $formdata['name']),
//			"name" => $formdata['name'],
			"country" => $formdata['country'],
			"order_date" => $formdata['createdat'],
			"total_amount" => $formdata['total_amount'],
			"address" => $formdata['street'],
			"pincode" => $formdata['postcode'],
			"quantity" => $formdata['qty'],
			"payment_mode" => $formdata['payment_mode'],
			"state" => $formdata['region'],
			"city" => $formdata['city'],
			"num_of_prds" => $formdata['qty'],
			"additional_info" => $rawData,
			"response" => $response,
			"created_at" => $date,
		]);
        
        
        
        
        
		$saveOrderData = $OrderModel->save();
		$qc_order_table_id = $OrderModel->getId(); 

		$order_inc = $formdata['incrementid'];

		foreach($pdata as $v){
			$ProductModel->setData([
				'qc_order_table_primary_id' => $qc_order_table_id,
				'item' => $v['attribute_set'],
				'product_category' => $v['attribute_set'],
				'images' => $v['images'],
				'description' => $v['name'],
				'return_reason' => $v['return_reason'],
				'quantity' => $v['qty_to_qc'],
				'brand' => $v['brand'],
				'incremented' => $order_inc,				
				'prd_sku' => $v['sku'],
				'prd_id' => $v['id'],
                "additional_info" => $v['question_data']
			]);	
			$saveProductData = $ProductModel->save();
		}

		$resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/');
        $resultRedirect->setUrl($this->_redirect->getRefererUrl()); // Redirect to the same page
        if(trim($waybill) == ''){
            $this->messageManager->addError(__('Waybill could not generated')); 
        }else{
            $this->messageManager->addSuccess(__('Waybill Created Successfuly. Waybill: '.$waybill.'.')); 
        }

        return $resultRedirect;
	}
}

?>