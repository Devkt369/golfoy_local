<?php
namespace ArtM\TradeIn\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $resultFactory;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\ResultFactory $resultFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->resultFactory = $resultFactory;
        return parent::__construct($context);
    }
    
    public function execute()
    {  
//        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
//        $redirect->setUrl('/');
//        return $redirect;
        return $this->_pageFactory->create();
    }
}


/*
namespace ArtM\TradeIn\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;

class Submit extends Action
{
    protected $uploaderFactory;

    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $postData = $this->getRequest()->getPostValue();

        // Validate and process the form data
        if ($postData) {
            // Validate the form data here

            // Save the images
            $images = $this->getRequest()->getFiles('images');
            $imagePaths = [];

            foreach ($images as $image) {
                try {
                    $uploader = $this->uploaderFactory->create(['fileId' => $image]);
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->setAllowCreateFolders(true);

                    $mediaDirectory = $this->_objectManager->get(\Magento\Framework\Filesystem::class)
                        ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

                    $result = $uploader->save($mediaDirectory->getAbsolutePath('tradein/images'));
                    $imagePaths[] = 'tradein/images' . $result['file'];
                } catch (\Exception $e) {
                    // Handle the exception here
                }
            }

            // Save the form data and image paths in the database
            // ...

            // Redirect or display success message
            $this->messageManager->addSuccessMessage('Form submitted successfully!');
            return $this->resultRedirectFactory->create()->setPath('tradein/index/success');
        }

        return $result;
    }
}
*/
