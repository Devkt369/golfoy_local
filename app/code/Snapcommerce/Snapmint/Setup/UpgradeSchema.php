<?php
namespace Snapcommerce\Snapmint\Setup;
use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;
/**
 * Class InstallData
 */
class UpgradeSchema implements UpgradeSchemaInterface
{     
    /**
     * Status Factory
     *
     * @var StatusFactory
     */
    protected $statusFactory;
    /**
     * Status Resource Factory
     *
     * @var StatusResourceFactory
     */
    protected $statusResourceFactory;
    /**
     * InstallData constructor
     *
     * @param StatusFactory $statusFactory
     * @param StatusResourceFactory $statusResourceFactory
     */
    public function __construct(
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }
    /**
     * Installs data for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     *
     * @throws Exception
     */
    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {   
        $this->addNewOrderProcessingCapturedStatus(); 
        $this->addNewOrderProcessingAuthorizedStatus(); 
        $this->addNewOrderProcessingPendingAuthorizedStatus(); 
    }
    /**
     * Create Captured order processing status and assign it to the existent state
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addNewOrderProcessingCapturedStatus() {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => \Snapcommerce\Snapmint\Model\Payment\Snapmint::ORDER_STATUS_AUTHORIZED_CODE,
            'label' => \Snapcommerce\Snapmint\Model\Payment\Snapmint::ORDER_STATUS_AUTHORIZED_LABEL,
        ]);
        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }
        $status->assignState(Order::STATE_PROCESSING, false, true);
    } 

    /**
     * Create Authorized order processing status and assign it to the existent state
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addNewOrderProcessingAuthorizedStatus() {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => \Snapcommerce\Snapmint\Model\Payment\Snapmint::ORDER_STATUS_CAPUTURED_CODE,
            'label' => \Snapcommerce\Snapmint\Model\Payment\Snapmint::ORDER_STATUS_CAPUTURED_LABEL,
        ]);
        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }
        $status->assignState(Order::STATE_PROCESSING, false, true);
    } 

    /**
     * Create Pending Authorization order processing status and assign it to the existent state
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addNewOrderProcessingPendingAuthorizedStatus() {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => \Snapcommerce\Snapmint\Model\Payment\Snapmint::ORDER_STATUS_AUTHORIZED_PENDING_CODE,
            'label' => \Snapcommerce\Snapmint\Model\Payment\Snapmint::ORDER_STATUS_AUTHORIZED_PENDING_LABEL,
        ]);
        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }
        $status->assignState(Order::STATE_PROCESSING, false, true);
    } 
}