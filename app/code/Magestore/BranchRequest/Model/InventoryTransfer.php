<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model;

use Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Url as FrontendUrl;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Backend\Model\Url as BackendUrl;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\Collection as TransferItems;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\CollectionFactory
    as TransferItemsFactory;

/**
 * Class BranchRequest InventoryTransfer
 *
 * @SuppressWarnings(PHPMD)
 */
class InventoryTransfer extends AbstractModel implements InventoryTransferInterface
{
    const XML_PATH_SENDER = 'inventory_movement/branch_requests/notifier_sender';
    const XML_PATH_EMAIL_NOTIFEREMAILS = 'inventory_movement/branch_requests/notifier_email_template';

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var TransportBuilder
     */
    public $transportBuilder;

    /**
     * @var SourceRepositoryInterface
     */
    public $sourceRepository;

    /**
     * @var FrontendUrl
     */
    public $urlBuilder;

    /**
     * @var BackendUrl
     */
    public $backendUrlManager;

    /**
     * @var TransferItemsFactory
     */
    public $transferItemsFactory;

    /**
     * InventoryTransfer construct
     *
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param SourceRepositoryInterface $sourceRepository
     * @param FrontendUrl $urlBuilder
     * @param BackendUrl $backendUrlManager
     * @param TransferItemsFactory $transferItemsFactory
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD)
     */
    public function __construct(
        StoreManagerInterface     $storeManager,
        ScopeConfigInterface      $scopeConfig,
        TransportBuilder          $transportBuilder,
        SourceRepositoryInterface $sourceRepository,
        FrontendUrl               $urlBuilder,
        BackendUrl                $backendUrlManager,
        TransferItemsFactory      $transferItemsFactory,
        Context                   $context,
        Registry                  $registry,
        AbstractResource          $resource = null,
        AbstractDb                $resourceCollection = null,
        array                     $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->sourceRepository = $sourceRepository;
        $this->urlBuilder = $urlBuilder;
        $this->backendUrlManager = $backendUrlManager;
        $this->transferItemsFactory = $transferItemsFactory;
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer::class);
    }

    /**
     * Source Name From
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFrom()
    {
        return $this->sourceRepository->get($this->getData(InventoryTransferInterface::FROM))
            ->getName();
    }

    /**
     * Source Name To
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTo()
    {
        return $this->sourceRepository->get($this->getData(InventoryTransferInterface::TO))
            ->getName();
    }

    /**
     * Get Transfer Items
     *
     * @return TransferItems
     */
    public function getTransferItems()
    {
        /** @var TransferItems $transferItems */
        $transferItems = $this->transferItemsFactory->create();
        return $transferItems->addFieldToFilter('inventorytransfer_id', $this->getInventorytransferId());
    }

    /**
     * Send Notifer Emails
     *
     * @return array
     */
    public function sendNotiferEmails()
    {
        $result = [];
        if (!$this->getNotiferEmails()) {
            $result['error'] = true;
            $result['message'] = __('The notifer emails is empty!');
        }
        $store = $this->storeManager->getStore();
        $templateId = $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_NOTIFEREMAILS,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );
        $sender = $this->scopeConfig->getValue(
            self::XML_PATH_SENDER,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );
        try {
            $notiferEmails = explode(
                ",",
                preg_replace('/\s+/', '', $this->getNotiferEmails())
            );
            foreach ($notiferEmails as $notiferEmail) {
                $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                    ->setTemplateOptions(['area' => 'frontend', 'store' => $store->getId()])
                    ->setTemplateVars(
                        [
                            'store' => $store,
                            'branch_request' => $this,
                            'view_url' => $this->backendUrlManager->getUrl(
                                'branchrequest/inventoryTransfer/edit',
                                ['id' => $this->getId()]
                            ),
                            'download_url' => $this->urlBuilder->getUrl(
                                'branchrequest/index/download',
                                ['id' => $this->getId()]
                            ),
                        ]
                    )
                    ->setFrom($sender)
                    ->addTo($notiferEmail, $notiferEmail)
                    ->getTransport();
                $transport->sendMessage();
            }
            $result['message'] = __('The branch request email was sent to %1 successfully.', $this->getNotiferEmails());
        } catch (\Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Get Status
     *
     * @return array|mixed|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Get Inventorytransfer Id
     *
     * @return array|mixed|null
     */
    public function getInventorytransferId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Get Qty Requested
     *
     * @return array|mixed|null
     */
    public function getQtyRequested()
    {
        return $this->getData(self::QTY_REQUESTED);
    }

    /**
     * Get Qty Delivered
     *
     * @return array|mixed|null
     */
    public function getQtyDelivered()
    {
        return $this->getData(self::QTY_DELIVERED);
    }

    /**
     * Get Qty Received
     *
     * @return array|mixed|null
     */
    public function getQtyReceived()
    {
        return $this->getData(self::QTY_RECEIVED);
    }

    /**
     * Show Delivery Tab
     *
     * @return bool
     */
    public function showDeliveryTab()
    {
        if (!$this->getInventorytransferId()
            || $this->getStatus() == self::STATUS_NEW
            || $this->getStatus() == self::STATUS_WAIT_APPROVE) {
            return false;
        }
        return true;
    }

    /**
     * Can Delivery
     *
     * @return bool
     */
    public function canDelivery()
    {
        if (($this->getStatus() == self::STATUS_APPROVED
                || $this->getStatus() == self::STATUS_PROCESSING)
            && $this->getQtyDelivered() < $this->getQtyRequested()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Show Receive Tab
     *
     * @return bool
     */
    public function showReceiveTab()
    {
        if (!$this->getInventorytransferId()
            || $this->getStatus() == self::STATUS_NEW
            || $this->getStatus() == self::STATUS_WAIT_APPROVE
            || $this->getStatus() == self::STATUS_APPROVED) {
            return false;
        }
        return true;
    }

    /**
     * Can Delivery
     *
     * @return bool
     */
    public function canReceive()
    {
        if ($this->getStatus() == self::STATUS_PROCESSING && $this->getQtyReceived() < $this->getQtyDelivered()) {
            return true;
        }
        return false;
    }
}
