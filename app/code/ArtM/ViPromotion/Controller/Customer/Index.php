<?php

namespace ArtM\ViPromotion\Controller\Customer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\ViPromotion\Helper\Data as ViPromotionHelper;

class Index extends Action
{
    protected $resultJsonFactory;
    protected $helper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ViPromotionHelper $helper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->helper->isCustomerInTargetGroup()) {
            return $result->setData([
                'success' => false
            ]);
        }

        $response = [
            'success' => true,
            'logo_url' => $this->helper->getCustomLogoUrl(),
            'initial_image_url' => $this->helper->getCustomInitialImageUrl(),
            'show_popup' => $this->helper->shouldShowPopup(),
            'popup_title' => $this->helper->getPopupTitle(),
            'popup_content' => $this->helper->getPopupContent(),
            'show_banner' => $this->helper->shouldShowBanner(),
            'banner_content' => $this->helper->getBannerContent()
        ];

        return $result->setData($response);
    }
}