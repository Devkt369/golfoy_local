<?php

namespace ArtM\CustomPrinting\Controller\Index;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Result\PageFactory;

class Ajaxupload extends \Magento\Framework\App\Action\Action
{
    protected $uploaderFactory;
    protected $adapterFactory;
    protected $filesystem;
    protected $resultJsonFactory;
    protected $resultPage;
    protected $adapter;
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Image\AdapterFactory $adapterFactory,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;  
        $this->resultJsonFactory = $resultJsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->adapterFactory = $adapterFactory;
        $this->filesystem = $filesystem;
    }
    public function execute(){
        $page = $this->resultPageFactory->create();
        $json = $this->resultJsonFactory->create(); 
        $mediafolder = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $hash = strtoupper(hash('adler32', rand(10,10000).time()) );
        $customprintfolder = $mediafolder.'/customprint/'.$hash.'/';
        if (!file_exists($customprintfolder)) {
            mkdir($customprintfolder, 0777, true);
        }
        $errors= [];
        try{
            if(isset($_FILES['file'])){                
                $file_name = $_FILES['file']['name'];
                $file_size =$_FILES['file']['size'];
                $file_tmp =$_FILES['file']['tmp_name'];
                $file_type=$_FILES['file']['type'];
               //$file_ext=strtolower(end(explode('.',$_FILES['file']['name'])));
               $temp= explode('.',$file_name);
                $file_ext = end($temp);  
               // $file_ext='png';
                $extensions= array("jpeg","jpg","png", "pdf", "eps","ai");
                
                if(in_array(strtolower($file_ext),$extensions)=== false){
                $errors[]="File format is not supported.";
                }
                
                if($file_size >  20000000){
                $errors[]='File size must be excately 20 MB';
                }  
                if(empty($errors)==true){
                $newfilename =   time().".".$file_ext; 
                $path = "customprint/".$hash."/".$newfilename;
                 //$path = '<a href="/media/customprint/'.$hash."/".$newfilename.'" target="blank">Image uploaded </a>';
                if(move_uploaded_file($file_tmp, $customprintfolder.$newfilename)){
                    return $json->setData(['path' => $path]);   
                }                                    
                }else{
                    return $json->setData(['error' => $errors]);   
                }
            } 
        }catch( \Exception $e ){
            $errors[] = 'Some error occured. Try agian.';
            return $json->setData(['error' => $errors]); 
        } 
    }
    
}
