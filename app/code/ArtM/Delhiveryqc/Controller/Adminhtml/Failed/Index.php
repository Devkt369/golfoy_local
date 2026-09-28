<?php

namespace ArtM\Delhiveryqc\Controller\Adminhtml\Failed;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResponseInterface;
use ArtM\Delhiveryqc\Model\OrderQc;
use ArtM\Delhiveryqc\Model\ProductQc;
use ArtM\Delhiveryqc\Model\OrderQcCommentFactory;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
	private $pageFactory;
	private $OrderData;
	private $ProductData;
    protected $orderQcCommentFactory;

	public function __construct(
		PageFactory $pageFactory, 
        ResultFactory $resultFactory,
        OrderQcCommentFactory $orderQcCommentFactory,
		Action\Context $context,
		\Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
		OrderQc $OrderData,
        \ArtM\Delhiveryqc\Helper\Data $helper,
		ProductQc $ProductData
		)
	{
		$this->pageFactory= $pageFactory;
        $this->resultFactory = $resultFactory;
		$this->orderQcCommentFactory = $orderQcCommentFactory;
		parent::__construct($context);
		$this->OrderData = $OrderData;
		$this->helper = $helper;
		$this->ProductData = $ProductData;
	}
    public function execute(){
        $data = $this->getRequest()->getParams();
        
        $incId = $data['fdata']['0'];
        $prdId = $data['fdata']['1'];
        $waybill = $data['fdata']['2'];
        
        
        $orderQcCommentModelCollection = $this->orderQcCommentFactory->Create()->getCollection();

        $orderQcColumn = $this->OrderData->getCollection()->addFieldToFilter('order', $incId)->addFieldToFilter('qc_id', $waybill);
        // ->addFieldToFilter('prd_id', $prdId );
        // echo '<pre>';
        // print_r($orderQcColumn->getData());
        // die('asdf');
        if($orderQcColumn->getSize() > 0){
//            $orderQcColumn = $orderQcColumn->getFirstItem();
            foreach($orderQcColumn as $orderQcColumnsingle){
                $orderQcColumnsingle->setData('status','failed');
                $orderQcColumnsingle->save();
            }
        }

        $orderQcCommentModel = $this->orderQcCommentFactory->Create()->getCollection();
        $qc_order_table_primary_id = $this->helper->qcNumberForComment($incId , $prdId);
        $orderQcCommentModels = $this->OrderData->getCollection()->addFieldToFilter('id', $qc_order_table_primary_id );

        if($orderQcCommentModels->getSize() > 0){
            $orderQcCommentModel = $orderQcCommentModel->getFirstItem();
            $orderQcCommentModel->setData([
                'qc_order_table_primary_id' => $qc_order_table_primary_id,
                'waybillnumber' => $waybill,
                'comment'=>'[failed]',
            ]);
            $orderQcCommentModel->save();
        }else{
            $orderQcCommentModel->setData([
                'qc_order_table_primary_id' => $qc_order_table_primary_id,
                'waybillnumber' => $waybill,
                'comment' => '[Failed]'
            ]);
            $orderQcCommentModel->save();
        }

        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData(['message' => 'Controller action executed']);
        return $result;
    }
}
