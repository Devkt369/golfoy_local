<?php

namespace ArtM\Review\Controller\Index;

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
    protected $resultJsonFactory;
    protected $resultPageFactory;
    protected $imageCompressor;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \RLTSquare\ProductReviewImages\Model\ReviewMediaFactory $_reviewMediaFactory,
        \Magento\Framework\Filesystem $filesystem,
        ImageCompressor $imageCompressor
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;  
        $this->resultJsonFactory = $resultJsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->_reviewMediaFactory = $_reviewMediaFactory;
        $this->filesystem = $filesystem;
        $this->imageCompressor = $imageCompressor;
    }
    public function execute(){
        $page = $this->resultPageFactory->create();
        $json = $this->resultJsonFactory->create(); 
        $mediaDirectory = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $target = $mediaDirectory->getAbsolutePath('review_images');
        $hash = strtoupper(hash('adler32', rand(10,10000).time()) );

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
            $newfilename =   $hash."_".time().".".$file_ext; 
            $path = "/".$newfilename;
            try{
            if(move_uploaded_file($file_tmp,  $target.$path)){
                $this->imageCompressor->maybeCompressImage($target . $path);
                return $json->setData(['path' => $path]); 
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
