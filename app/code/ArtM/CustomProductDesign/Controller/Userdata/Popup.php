<?php
namespace ArtM\CustomProductDesign\Controller\Userdata;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use ArtM\CustomProductDesign\Helper\Data as CustomHelper;

class Popup extends Action
{
    protected $resultPageFactory;
    protected $customHelper;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CustomHelper $customHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customHelper = $customHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $userdataId = $this->getRequest()->getParam('id');
            
            // Clean up the ID(s)
            if ($userdataId && is_string($userdataId)) {
                // Remove any whitespace
                $userdataId = trim($userdataId);
                
                // Check if it's a comma-separated list
                if (strpos($userdataId, ',') !== false) {
                    // Handle comma-separated list
                } else {
                    // Only try JSON decode if it looks like JSON (starts with { or [)
                    if (substr($userdataId, 0, 1) === '{' || substr($userdataId, 0, 1) === '[') {
                        $decodedId = json_decode($userdataId, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            if (is_array($decodedId) && isset($decodedId[0]['id'])) {
                                $userdataId = $decodedId[0]['id'];
                            } elseif (isset($decodedId['id'])) {
                                $userdataId = $decodedId['id'];
                            }
                        }
                    }
                    // If not JSON, use as is (single ID)
                }
            }
            
            $customizationData = $this->customHelper->getCustomizationData($userdataId);
            
            if (!$customizationData) {
                throw new \Exception('No customization data found');
            }
            
            /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
            $resultRaw = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
            $resultRaw->setHeader('Content-Type', 'text/html');
            
            // Generate the HTML using the helper
            $html = $this->customHelper->getCustomizationPopupHtml($customizationData);
            $resultRaw->setContents($html);
            $resultRaw->setContents($html);
            
            return $resultRaw;
        } catch (\Exception $e) {
            $resultRaw = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
            $resultRaw->setHeader('Content-Type', 'text/html');
            $resultRaw->setContents('<div class="message error">' . $e->getMessage() . '</div>');
            return $resultRaw;
        }
    }
}