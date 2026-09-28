<?php

namespace ArtM\CancelOrder\Controller\Adminhtml\Request;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\Auth\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Meetanshi\Sms\Helper\Data;

class Update extends Action
{
    protected $status;
    protected $resultFactory;
    protected $redirect;
    protected $backendAuthSession;
    protected $orderFactory;
    protected $productRepository;
    protected $_smsHelper;
    protected $resultPageFactory;
    protected $CancelOrderCustom;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \ArtM\CancelOrder\Model\CancelOrderCustomFactory $CancelOrderCustom,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        Data $_smsHelper,
        ProductRepositoryInterface $productRepository,
        ResultFactory $resultFactory,
        Session $backendAuthSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->resultPageFactory = $resultPageFactory;
        $this->CancelOrderCustom = $CancelOrderCustom;
        $this->resultFactory = $resultFactory;
        $this->backendAuthSession = $backendAuthSession;
        $this->orderFactory = $orderFactory;
        $this->productRepository = $productRepository;
        $this->_smsHelper = $_smsHelper;
    }

    public function execute()
    {
        $request = $this->getRequest();
        $formdata = $request->getPostValue();

        if ($formdata['updated_request_type']  == 'replace Approved') {
            $data = [
                'orderId'       => $formdata['incremented_id'],
                'firstname'     => $formdata['customer_name'],
                'product_name'  => ''
            ];
            $observerName = 'order_replace_approved';
            $this->_smsHelper->sendSmsReplaceApproved($data, $formdata['customer_phone'], $observerName);
        } else {
            $data = [
                'orderId'       => $formdata['incremented_id'],
                'firstname'     => $formdata['customer_name'],
                'product_name'  => ''
            ];
            $observerName = 'order_return_approved';
            $this->_smsHelper->sendSmsReturnApproved($data, $formdata['customer_phone'], $observerName);
        }
        $this->status = $formdata['updated_request_type'];
        $order_id = $formdata['order_id'];
        $prd_id = $formdata['prd_id'];


        $this->checkOrderProduct($order_id, $prd_id);
        $this->updateOrderStaus($order_id, $prd_id);

        $product = $this->productRepository->getById($prd_id);
        $productName = $product->getName();

        $adminUserName = $this->backendAuthSession->getUser()->getUsername();

        $order = $this->orderFactory->create()->load($order_id);
        $status = $order->getStatus();
        $state = $order->getState();

        $order->setStatus($this->checkOrderStatus($order_id));
        $order->setState($state);
        $order->addStatusToHistory($order->getStatus(), " Admin User:- " . $adminUserName . " ||Product Name:- " . $productName);
        $order->save();

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/');
        $resultRedirect->setUrl($this->_redirect->getRefererUrl()); // Redirect to the same page
        $this->messageManager->addSuccess(__('Order Saved Successfuly ...'));

        return $resultRedirect;
    }


    public function checkOrderProduct($order_id, $prd_id)
    {

        $CancelOrderCustomobj = $this->CancelOrderCustom->create();
        $CancelOrderCustomcollection = $CancelOrderCustomobj
            ->getCollection()
            ->addFieldToFilter('order_id', $order_id)
            ->addFieldToFilter('product_ids', $prd_id);

        if ($CancelOrderCustomcollection->count() == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function updateOrderStaus($order_id, $prd_id)
    {
        if ($this->checkOrderProduct($order_id, $prd_id) == true) {

            $CancelOrderCustomobj = $this->CancelOrderCustom->create();
            $CancelOrderCustomcollection = $CancelOrderCustomobj
                ->getCollection()
                ->addFieldToFilter('order_id', $order_id)
                ->addFieldToFilter('product_ids', $prd_id);

            $id = 0;
            foreach ($CancelOrderCustomcollection as $CancelOrderCustom) {
                $id = $CancelOrderCustom['id'];
                break;
            }
            $CancelOrderCustomorder = $this->CancelOrderCustom->create();

            $CancelOrderCustomorder->load($id);

            $CancelOrderCustomorder->setCurrentStatus($this->status);
            $CancelOrderCustomorder->save();
        }
    }

    public function checkOrderStatus($orderId)
    {
        $collection = $this->CancelOrderCustom->create()
            ->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->setOrder('id', 'DESC')
            ->setPageSize(1);

        $item = $collection->getFirstItem();

        if ($item->getId()) {
            return $item->getOrderStatus();
        }

        return false;
    }
}
