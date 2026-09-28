<?php

namespace ArtM\CancelOrder\Controller\Index;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Result\PageFactory;
use ArtM\Common\Model\ImageCompressor;

class Ajaxupload extends \Magento\Framework\App\Action\Action
{
    protected $uploaderFactory;
    protected $adapterFactory;
    protected $filesystem;
    protected $_request;
    protected $_reviewMediaFactory;
    protected $resultPageFactory;
    protected $resultJsonFactory;
    protected $imageCompressor;
    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \RLTSquare\ProductReviewImages\Model\ReviewMediaFactory $reviewMediaFactory,
        \Magento\Framework\Filesystem $filesystem,
        ImageCompressor $imageCompressor

    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;  
        $this->resultJsonFactory = $resultJsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->_reviewMediaFactory = $reviewMediaFactory;
        $this->filesystem = $filesystem;
        $this->imageCompressor = $imageCompressor;
    }

    public function execute(){
        $page = $this->resultPageFactory->create();
        $json = $this->resultJsonFactory->create(); 
        $mediaDirectory = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $target = $mediaDirectory->getAbsolutePath('return_image');
        $hash = hash('adler32', rand(10,10000).time());
        $return_orderid = $_GET['orderid'];
        
        
        if(isset($_FILES['files'])){  
            $errors= [];
            $file_name = $_FILES['files']['name'][0];
            $file_size =$_FILES['files']['size'][0];
            $file_tmp =$_FILES['files']['tmp_name'][0];
            $file_type=$_FILES['files']['type'][0];
            $temp= explode('.',$file_name);
            $file_ext = end($temp);  
            $extensions= array("jpeg","jpg","png");
            
            if(in_array(strtolower($file_ext),$extensions)=== false){
            $errors[]="Extension not allowed.";
            }
            if($file_size >  20000000){
            $errors[]='File size must be excately 20 MB';
            }  
            if(empty($errors)==true){
           // $newfilename =   $return_orderid."_".time().".".$file_ext; 
           $newfilename =   $return_orderid."_".$hash.".".$file_ext;
           
           $path = "/".$newfilename;

            try{
                if(!is_dir("$target/$return_orderid/")) {
                    mkdir("$target/$return_orderid/");
                }
                $target = "$target/$return_orderid/";
                
                $savedFilePath = $target . $path;
                if(move_uploaded_file($file_tmp,  $savedFilePath)){
                    $this->imageCompressor->maybeCompressImage($savedFilePath);
                    return $json->setData(['path' => $path]);
                }     
                else{
                    $errors[] = 'Failed to move uploaded file.';
                    return $json->setData(['error' => $errors]);
                }
            }catch( \Exception $e ){
                $errors[] = $e->getMessage();
                return $json->setData(['error' => $errors]);    
            }                 
            }else{
                return $json->setData(['error' => $errors]);   
            }
        }
   }
} 
