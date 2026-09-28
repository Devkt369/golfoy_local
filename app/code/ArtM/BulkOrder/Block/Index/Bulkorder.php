<?php

namespace ArtM\BulkOrder\Block\Index;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Escaper;

class Bulkorder extends \Magento\Framework\View\Element\Template
{
    public $bulkorder;
    protected $messageManager;
    protected $transportBuilder;
    public $datainserted;
    protected $escaper;
    public function __construct( \Magento\Framework\View\Element\Template\Context $context,
    \ArtM\BulkOrder\Model\Bulkorder $bulkorder,
    TransportBuilder $transportBuilder,
    Escaper $escaper,
    \Magento\Framework\Message\ManagerInterface $messageManager,
    array $data = [])
    {  
        $this->bulkorder = $bulkorder;
        $this->messageManager = $messageManager;
        $this->transportBuilder = $transportBuilder;
        $this->escaper = $escaper;
        parent::__construct($context, $data);
    }
   public function insertData(){ 
         $data = $this->getData('formdata');
         $arr = [];
         if(!empty($data)){
            
            $model = $this->bulkorder;
    
            $arr=[
                'name'=>$this->escaper->escapeHtml($data['name']),
                'email'=>$this->escaper->escapeHtml($data['email']),
                'telephone'=>$this->escaper->escapeHtml($data['telephone']),
                'comment'=>$this->escaper->escapeHtml($data['comment'])
             ];

            $model->setData($arr);

            $datainserted = $model->save();
            if($datainserted){
                $this->messageManager->addSuccess(__("Success"));
                $this->send_mail();
                header("refresh: 5;");
                }else{
                $this->messageManager->addError(__("Error"));
            }
           // return $message;
        }
    }

    

    public function send_mail(){  
        $data = $this->getData('formdata');
        $name = $this->escaper->escapeHtml($data['name']);
        $c_email = $data['email'];
        $cmnt = $this->escaper->escapeHtml($data['comment']);
       
        //$storeId = $this->storeManager->getStore()->getStoreId();
        $templateVars = [];
        $sendor_name = 'Golfoy.com';
        $sendor_email = 'contact@golfoy.com';
        $bcc = 'tech1@golfoy.com';
        $subject = "Bulk Order Request";
        $templateidentifier = '12';
        $content = $cmnt;
        if($sendor_name != '' || $sendor_email != '' || $bcc != '' || $content != '' ){
            //$subject   = $recipient_name.', '.$subject;
                $templateVars = [
                'cname' => $name,
                'subj'  => $subject,
                'mail_body' => $content
            ];          
            $this->transportBuilder
            ->setTemplateIdentifier($templateidentifier)
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => 1])
            ->setTemplateVars($templateVars)
            ->setFrom(['name' => $sendor_name,'email' => $sendor_email])
            //->addTo([$recipient_email])
            ->addTo([$c_email])
            ->addBcc(['contact@golfoy.com','kunal@golfoy.com']);
            $transport = $this->transportBuilder->getTransport();

            if (!isset($transport)) {
                $transport = $this->transportBuilder->getTransport();
            }
            if($transport->sendMessage() ){
                
            }
    
            }       

    }  
}
