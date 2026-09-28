<?php

namespace ArtM\RecommendedFilter\Controller\Reindex;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
class Logic extends Action
{
    protected $ResultFactory;
    
    public function __construct(
        Context $context,
        \ArtM\RecommendedFilter\Helper\Data $recommend_filter_helper,
        \Magento\Framework\App\Request\Http $request,

        PageFactory $PageFactory
    )
    {
        $this->PageFactory = $PageFactory;
        $this->request = $request;
		$this->recommend_filter_helper = $recommend_filter_helper;
        parent::__construct($context);
    }

    public function execute()
    {
        if($this->request->getParam('id')){
            $id = $this->request->getParam('id');
            if($id == 'none'){
                $this->recommend_filter_helper->CalculatePoints();
            }else{
                $this->recommend_filter_helper->UpdatePoints( $id );
            }
        }else{
            echo "Id is not set";
        }
//        $this->recommend_filter_helper->UpdatePoints( 25094 );
//        $this->recommend_filter_helper->UpdatePoints( 13567 );
//        $this->recommend_filter_helper->UpdatePoints( 25112 );
//          $this->recommend_filter_helper->UpdatePoints( 20431 );
//            echo "<br>-----------------------------------------------------------------------------------------------------------------------------<br>";
//          $this->recommend_filter_helper->UpdatePoints( 17532 );
        
//20214
//3922
//        23
        
        


        exit;
    }
}



