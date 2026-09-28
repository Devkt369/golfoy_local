<?php

namespace ArtM\TradeIn\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\TradeIn\Model\TradeInFactory;

class InlineEdit extends Action
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
     * Inline edit action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $tradeInId) {
                    /** @var \ArtM\TradeIn\Model\TradeIn $tradeIn */
                    $tradeIn = $this->tradeInFactory->create()->load($tradeInId);
                    if (!$tradeIn->getId()) {
                        $messages[] = "[Trade-In ID: {$tradeInId}] Record not found.";
                        $error = true;
                        continue;
                    }
                    try {
                        $itemData = $postItems[$tradeInId];

                        // Handle payment fields specially
                        $paymentUpdated = false;
                        if (isset($itemData['payment_method']) || isset($itemData['ref_id']) || isset($itemData['amount'])) {
                            // Get existing payment data
                            $existingPayment = $tradeIn->getPaymentComment();
                            $paymentData = [];
                            if (!empty($existingPayment)) {
                                $decoded = json_decode($existingPayment, true);
                                if (is_array($decoded)) {
                                    $paymentData = $decoded;
                                }
                            }

                            // Update payment data with new values
                            if (isset($itemData['payment_method'])) {
                                $paymentData['payment_comment'] = $itemData['payment_method'];
                            }
                            if (isset($itemData['ref_id'])) {
                                $paymentData['ref_id'] = $itemData['ref_id'];
                            }
                            if (isset($itemData['amount'])) {
                                $paymentData['amount'] = $itemData['amount'];
                            }

                            $tradeIn->setPaymentComment(json_encode($paymentData));
                            $paymentUpdated = true;

                            // Remove these from the regular data update
                            unset($itemData['payment_method'], $itemData['ref_id'], $itemData['amount']);
                        }

                        // Cast is_listed to int (select editor sends string "1"/"0")
                        if (array_key_exists('is_listed', $itemData)) {
                            $itemData['is_listed'] = (int)$itemData['is_listed'];
                        }
                        if (array_key_exists('show_customer_expected_price', $itemData)) {
                            $itemData['show_customer_expected_price'] = (int)$itemData['show_customer_expected_price'];
                        }
                        if (array_key_exists('show_our_offered_price', $itemData)) {
                            $itemData['show_our_offered_price'] = (int)$itemData['show_our_offered_price'];
                        }

                        // Update other fields
                        if (!empty($itemData)) {
                            foreach ($itemData as $key => $value) {
                                $tradeIn->setData($key, $value);
                            }
                        }

                        $tradeIn->save();

                        if ($paymentUpdated) {
                            $messages[] = __('Payment information updated for Trade-In ID: %1', $tradeInId);
                        }
                    } catch (\Exception $e) {
                        $messages[] = "[Trade-In ID: {$tradeInId}] {$e->getMessage()}";
                        $error = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
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
