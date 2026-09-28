<?php
namespace ArtM\Review\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

class Check extends Action
{
    protected $resultJsonFactory;
    protected $resultPageFactory;
    protected $customerSession;
    protected $helper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \ArtM\Review\Helper\Data $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession   = $customerSession;
        $this->helper            = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $this->getResponse()->setHeader('Cache-Control', 'no-store, no-cache', true);
        $this->getResponse()->setHeader('Pragma', 'no-cache', true);

        // CHECK 1 — not logged in
        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData([
                'success' => true,
                'html'    => '',
                'reason'  => 'customer_not_logged_in'
            ]);
        }

        // CHECK 2 — module not enabled
        $moduleStatus = $this->helper->getConfig('review/general/module');
        if ($moduleStatus != 1) {
            return $result->setData([
                'success' => true,
                'html'    => '',
                'reason'  => 'module_not_enabled'
            ]);
        }

        // CHECK 3 — get orders
        $customerId = $this->customerSession->getCustomerId();
        $to_days    = (int)$this->helper->getConfig('review/general/to_days_default_60');
        $from_days  = (int)$this->helper->getConfig('review/general/from_days_default_30');

        if ($to_days == 0)   { $to_days   = 180; }
        if ($from_days == 0) { $from_days = 1;   }

        $ordersVar = $this->helper->getorders($customerId, $to_days, $from_days);

        // CHECK 4 — no orders found
        if (!$ordersVar) {
            return $result->setData([
                'success' => true,
                'html'    => '',
                'reason'  => 'no_orders_found'
            ]);
        }

        // CHECK 5 — already reviewed
        // $alreadyReviewed = $this->helper->checkIfAlreadyShown($ordersVar['order_id']);
        // if ($alreadyReviewed) {
        //     return $result->setData([
        //         'success' => true,
        //         'html'    => '',
        //         'reason'  => 'already_reviewed'
        //     ]);
        // }

        // ALL CHECKS PASSED — render block with data
        try {
            $resultPage = $this->resultPageFactory->create();

            $block = $resultPage->getLayout()
                ->createBlock(\ArtM\Review\Block\Popupdesign::class)
                ->setTemplate('ArtM_Review::popup_design/popup_design.phtml')
                ->setData('order_data',  $ordersVar)   // ← pass order data
                ->setData('customer_id', $customerId); // ← pass customer id

            $html = $block->toHtml();

            return $result->setData([
                'success' => true,
                'html'    => $html,
                'debug'   => [
                    'html_length'  => strlen($html),
                    'order_id'     => $ordersVar['order_id'],
                    'customer_id'  => $customerId,
                    'prd_ids'      => $ordersVar['prd_ids'],
                ]
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'html'    => '',
                'error'   => $e->getMessage()
            ]);
        }
    }
}