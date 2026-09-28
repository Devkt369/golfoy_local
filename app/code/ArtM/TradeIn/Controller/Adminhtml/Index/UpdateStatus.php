<?php

namespace ArtM\TradeIn\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\TradeIn\Model\TradeInFactory;

class UpdateStatus extends Action
{
    protected $jsonFactory;
    protected $tradeInFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        TradeInFactory $tradeInFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->tradeInFactory = $tradeInFactory;
    }

    /**
     * Update status action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $tradeInId = $this->getRequest()->getParam('tradein_id');
        $status = $this->getRequest()->getParam('status');
        $paymentComment = $this->getRequest()->getParam('payment_comment');
        $refId = $this->getRequest()->getParam('ref_id');
        $amount = $this->getRequest()->getParam('amount');
        $paymentMethod = $this->getRequest()->getParam('payment_method');
        $otherDetails = $this->getRequest()->getParam('other_details');

        try {
            $tradeIn = $this->tradeInFactory->create()->load($tradeInId);
            
            if (!$tradeIn->getId()) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Trade-in record not found.')
                ]);
            }

            // Update status if provided
            if ($status) {
                $tradeIn->setStatus($status);
            }

            // Handle payment info update
            if ($paymentComment !== null || $refId !== null || $amount !== null) {
                $paymentData = [
                    'payment_comment' => $paymentMethod === 'other' ? $otherDetails : $paymentMethod,
                    'ref_id' => $refId,
                    'amount' => $amount
                ];
                $tradeIn->setPaymentComment(json_encode($paymentData));
            }

            $tradeIn->save();

            return $resultJson->setData([
                'success' => true,
                'message' => __('Trade-in updated successfully.')
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Error updating trade-in: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Check permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_TradeIn::tradein');
    }
}
