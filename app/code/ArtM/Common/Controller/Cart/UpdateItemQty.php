<?php

namespace ArtM\Common\Controller\Cart;

use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * UpdateItemQty ajax request
 */
class UpdateItemQty extends \Magento\Checkout\Controller\Cart\UpdateItemQty
{
    /**
     * @var RequestQuantityProcessor
     */
    private $quantityProcessor;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;
    protected $_resultJsonFactory;

    /**
     * UpdateItemQty constructor
     *
     * @param Context $context
     * @param RequestQuantityProcessor $quantityProcessor
     * @param FormKeyValidator $formKeyValidator
     * @param CheckoutSession $checkoutSession
     * @param Json $json
     * @param LoggerInterface $logger
     * @param JsonFactory $_resultJsonFactory
     */

    public function __construct(
        Context $context,
        RequestQuantityProcessor $quantityProcessor,
        FormKeyValidator $formKeyValidator,
        CheckoutSession $checkoutSession,
        Json $json,
        LoggerInterface $logger,
        JsonFactory $_resultJsonFactory
    ) {
        $this->quantityProcessor = $quantityProcessor;
        $this->formKeyValidator = $formKeyValidator;
        $this->checkoutSession = $checkoutSession;
        $this->json = $json;
        $this->logger = $logger;
        $this->_resultJsonFactory = $_resultJsonFactory;
        parent::__construct(
            $context,
            $quantityProcessor,
            $formKeyValidator,
            $checkoutSession,
            $json,
            $logger
        );
    }

    /**
     * Controller execute method
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->validateRequest();
            $this->validateFormKey();

            $cartData = $this->getRequest()->getParam('cart');

            $this->validateCartData($cartData);

            $cartData = $this->quantityProcessor->process($cartData);
            $quote = $this->checkoutSession->getQuote();
            // die('asfsdf');
            $response = [];
            foreach ($cartData as $itemId => $itemInfo) {
                $item = $quote->getItemById($itemId);
                $qty = isset($itemInfo['qty']) ? (double) $itemInfo['qty'] : 0;
                if ($item) {
                    try {
                        // THIS IS WHERE YOU CAN ADD YOUR CUSTOM LOGIC
                        // For example, to check if the quantity is valid
                        if ($qty > 100) {
                            throw new LocalizedException(__('You cannot add more than 100 of this item.'));
                        }

                        $this->updateItemQuantity($item, $qty);
                    } catch (LocalizedException $e) {
                        $response[] = [
                            'error' => $e->getMessage(),
                            'itemId' => $itemId
                        ];
                    }
                }
            }

            $this->jsonResponse(count($response)? json_encode($response) : '');
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->jsonResponse('Something went wrong while saving the page. Please refresh the page and try again.');
        }
    }
}