<?php
namespace ArtM\TradeIn\Controller\Index;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class Submit extends \Magento\Framework\App\Action\Action
{
    protected $scopeConfig;
    protected $_pageFactory;
    protected $resultFactory;
    protected $filesystem;
    protected $fileUploader;
    protected $mediaDirectory;
    protected $_helper;
    protected $_session;
    protected $resultJsonFactory;
    protected $formmodel;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $_pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
	    \Magento\Customer\Model\Session $_session,
        \ArtM\TradeIn\Model\TradeIn $formmodel,
        ScopeConfigInterface $scopeConfig,
        Filesystem $filesystem,
        UploaderFactory $fileUploader,
        \ArtM\TradeIn\Helper\Data $_helper        
    )
    {
        $this->_pageFactory = $_pageFactory;
        $this->formmodel = $formmodel;
        $this->filesystem = $filesystem;
        $this->fileUploader = $fileUploader;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->resultJsonFactory = $resultJsonFactory;
	    $this->_session = $_session;
	    $this->_helper = $_helper;
        $this->scopeConfig = $scopeConfig;
        return parent::__construct($context);
    }
    public function execute()
    {
        $rawBody = $this->getRequest()->getContent();
        $postData = json_decode($rawBody, true); // Decode into associative array
        // echo "<pre>";
        // print_r($postData);
        // echo "</pre>";die;
        $json = $this->resultJsonFactory->create();

        $message = '';
        $status = '';
        $id = '';

        if (!empty($postData['items']) && is_array($postData['items'])) {
            $ad_id = $postData['ad_id'];
            $emailDataCombined = [];
            $to_email = '';
            $to_name = '';

            foreach ($postData['items'] as $item) {
                $formmodelvar = $this->formmodel;
                $formmodelvar->setData([
                    "equipment" => $item['equipment'] ?? '',
                    "brand" => $item['brand'] ?? '',
                    "model" => $item['prd_model'] ?? '',
                    "specification" => $item['prd_specification'] ?? '',
                    "condition" => $item['condition'] ?? '',
                    "images" => $item['images'] ?? '',
                    "name" => $item['name'] ?? '',
                    "contact_number" => $item['phone'] ?? '',
                    "ad_id" => $ad_id,
                    "status" => 'Pending',
                    "cust_id" => $item['cust_id'] ?? '',
                    "email" => $item['email'] ?? '',
                    "purchase_date" => $item['purchase_date'] ?? '',
                    "invoice_file" => $item['invoice_upload'] ?? '',
                    "order_id" => $item['order_id'] ?? '',
                    "seller_name" => $item['seller_name'] ?? '',
                    "purchased_from" => $item['purchased_from'] ?? ''
                ]);

                // Save the item
                if ($formmodelvar->save()) {
                    $id = $formmodelvar->getId();
                    $status = 'Success';

                    // For sending one email — collect combined data
                    $emailDataCombined[] = [
                        'equipment' => $item['equipment'] ?? '',
                        'brand' => $item['brand'] ?? '',
                        'model' => $item['prd_model'] ?? '',
                        'specification' => $item['prd_specification'] ?? '',
                        'condition' => $item['condition'] ?? '',
                        'images' => $item['images'] ?? '',
                    ];

                    $to_email = $item['email'] ?? '';
                    $to_name = $item['name'] ?? '';
                }
            }
            
            $productHtml = '
                <div style="padding: 30px 20px; text-align: center;">
                    <h2 style="text-transform: uppercase; font-size: 16px; letter-spacing: 0.5px; margin-bottom: 20px;">
                        <span style="border-bottom: 1px solid #000000; font-weight: 900;">Product Details</span>
                    </h2>
                    <table style="margin: 0 auto; font-size: 14px; text-align: left; border-collapse: collapse; width: 100%;">
                        <thead>
                            <tr style="background-color: #f5f5f5;">
                                <th style="padding: 8px; border: 1px solid #ddd;">Equipment</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Brand</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Model</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Specification</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Condition</th>
                            </tr>
                        </thead>
                        <tbody>';

                foreach ($emailDataCombined as $item) {
                    $productHtml .= '
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">' . $item['equipment'] . '</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">' . $item['brand'] . '</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">' . $item['model'] . '</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">' . $item['specification'] . '</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">' . $item['condition'] . '</td>
                            </tr>';
                }

                $productHtml .= '
                        </tbody>
                    </table>
                </div>';
            // Send one email after all items are saved
            if (!empty($emailDataCombined) && $to_email != '') {
                $from_name = "Golfoy.com Sales";
                $from_email = "sales@golfoy.com";

                // Send single email with combined product data
                $this->_helper->sendEmail(
                    $to_email,
                    $to_name,
                    $from_email,
                    $from_name,
                    $ad_id,
                    14,
                    $productHtml, // pass all products
                    $ad_id
                );

                $message = 'All items submitted successfully';
            } else {
                $message = 'No valid items to submit';
                $status = 'Error';
            }
        }
        
        $json->setData(['status' => $status]);
        return $json->setData(['message' => $message , 'status' => $status , 'id' => $id ]);
    }
    
    public function uploadImage()
    {
        $folder = 'tradein_invoice/';
        $inputName = 'invoice_upload';
        try {
            $file = $this->getRequest()->getFiles($inputName);
            if ($file && isset($file['name'])) {
                $target = $this->mediaDirectory->getAbsolutePath($folder);
                $uploader = $this->fileUploader->create(['fileId' => $inputName]);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'pdf','JPG', 'JPEG', 'PNG', 'PDF']);
                $uploader->setAllowCreateFolders(true);
                $uploader->setAllowRenameFiles(true);
                $result = $uploader->save($target);
                return $folder . $uploader->getUploadedFileName();

            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }
}

