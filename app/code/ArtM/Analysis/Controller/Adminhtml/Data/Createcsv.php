<?php

namespace ArtM\Analysis\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\UrlInterface;

class Createcsv extends Action
{
    
    protected $directoryList;
    protected $fileFactory;

    
    public function __construct(
        Action\Context $context,
        DirectoryList $directoryList,
        FileFactory $fileFactory,
        \ArtM\Analysis\Helper\Data $helper,
		\Magento\Framework\App\RequestInterface $request,
        JsonFactory $resultJsonFactory,
        UrlInterface $urlBuilder 
    ) {
        parent::__construct($context);
        $this->directoryList       = $directoryList;
        $this->fileFactory         = $fileFactory;
        $this->helper              = $helper;
		$this->request             = $request;
        $this->_resultJsonFactory  = $resultJsonFactory;
        $this->_urlBuilder         = $urlBuilder;
    }

    public function execute()
    {   
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        $result = $this->_resultJsonFactory->create();

        $params = $this->getRequest()->getParams();
        
        $data =[];
        if($params['st_date'] !=''){
            $from            = $params['st_date'];
            $fromDate        = date('Y-m-d', strtotime($params['st_date']));
			$from            = $fromDate." 00:00:00";

            if($params['st_date'] ==''){
                $toDate      = date('Y-m-d');
            }else{
                $toDate      = date('Y-m-d', strtotime($params['end_date']));
            }
			$to              = $toDate." 23:59:59";
            $data['stdate']  = $from ;
            $data['enddate'] = $to ;
        }
        if($params['brandattr'] !='' ){
            $data['brandattr'] = (int) $params['brandattr'] ;
        }

        if($params['limitvalue'] !='' AND $params['limitvalue'] != 0){
            $data['limit'] = (int) $params['limitvalue'] ;
        }
        $data['ftype'] = $params['ftype'];
        $attrset = explode(',', $params['attrset']);
        $data['attrset'] = $attrset;
        $fileName = $this->helper->prepareCsv($data);  
        $durl = $this->_urlBuilder->getUrl('analysis/data/export', ['id' => $fileName]);
        $link = "<a href= $durl > $fileName </a>";  
        $result->setData(['file' => $fileName, 'link' => $link ]);
        return $result;           
    }
}
