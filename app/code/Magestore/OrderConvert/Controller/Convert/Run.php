<?php
namespace Magestore\OrderConvert\Controller\Convert;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magestore\Webpos\Model\ResourceModel\Checkout\PosOrder\Grid\CollectionFactory as PosOrderCollectionFactory;
use Magestore\Webpos\Model\Checkout\PosOrder\Consumer;
use Magento\Framework\View\Result\PageFactory;

class Run extends Action
{
    protected $posOrderCollectionFactory;
    protected $consumer;
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PosOrderCollectionFactory $posOrderCollectionFactory,
        Consumer $consumer,
        PageFactory $resultPageFactory
    ) {
        $this->posOrderCollectionFactory = $posOrderCollectionFactory;
        $this->consumer = $consumer;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        // 1. Create a normal Magento page result
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Order Conversion Summary'));

        // 2. Get your layout block and pass data to it
        $layout = $resultPage->getLayout();
        $block = $layout->getBlock('orderconvert_result');

        if ($block) {
            $collection = $this->posOrderCollectionFactory->create();
            $collection->addFieldToFilter('queueing_status', ['eq' => 'processing']);

            $converted = [];
            $failed = [];

            foreach ($collection as $posOrder) {
                $incrementId = $posOrder->getIncrementId();

                try {
                    $this->consumer->execute($incrementId);
                    $converted[] = $incrementId;
                } catch (\Exception $e) {
                    $failed[] = [
                        'increment_id' => $incrementId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Pass to block for rendering
            $block->setData('converted', $converted);
            $block->setData('failed', $failed);
        }

        return $resultPage;
    }
}
