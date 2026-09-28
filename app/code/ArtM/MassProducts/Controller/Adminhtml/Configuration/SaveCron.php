<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Configuration;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class SaveCron extends Action
{
    private $resultJsonFactory;
    private $configWriter;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        WriterInterface $configWriter
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configWriter = $configWriter;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $time = $this->getRequest()->getParam('upload_time');
        $tz = $this->getRequest()->getParam('timezone');
        if (!$time || !$tz) {
            return $result->setData(['success' => false, 'message' => 'Missing data']);
        }
        $this->configWriter->save('artm_massproducts/cron/upload_time', $time);
        $this->configWriter->save('artm_massproducts/cron/timezone', $tz);
        return $result->setData(['success' => true]);
    }
}
