<?php
namespace ArtM\SFMC\Controller\AjaxSync;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\SFMC\Helper\Data as SfmcHelper;
use Magento\Framework\Exception\LocalizedException;

class OrderSync extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var SfmcHelper
     */
    protected $sfmcHelper;

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SfmcHelper $sfmcHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->sfmcHelper = $sfmcHelper;
        parent::__construct($context);
    }

    /**
     * Execute method — handle AJAX call
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $orderId = (int)$this->getRequest()->getParam('order_id');

            if (!$orderId) {
                throw new LocalizedException(__('Missing order_id parameter.'));
            }

            // Call the helper method
            $response = $this->sfmcHelper->SyncOrder($this->getOrderId($orderId));
            if($response){
                return $resultJson->setData([
                    'success' => true,
                    'message' => __('Order synced successfully.'),
                    'response' => $response
                ]);
            }else{
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Order Failed to synced.'),
                ]);
            }

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function getOrderId($increment_id){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $objectManager->create('Magento\Sales\Model\Order'); 
        $orderInfo = $collection->loadByIncrementId($increment_id);
        return $orderInfo->getId();
    }
}
