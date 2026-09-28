<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml;

/**
 * Class BranchRequest AbstractAction
 *
 * @SuppressWarnings(PHPMD)
 */
abstract class AbstractAction extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $_resultForwardFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $_resultLayoutFactory;

    /**
     * @var \Magento\Framework\Logger\Monolog
     */
    protected $_logger;

    /**
     * @var \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer
     */
    protected $inventoryTransferResource;
    /**
     * @var \Magestore\BranchRequest\Model\InventoryTransferFactory
     */
    protected $inventoryTransferFactory;
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;
    /**
     * @var \Magestore\BranchRequest\Api\TransferManagementInterface
     */
    protected $transferManagement;

    protected $_session;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Magestore\BranchRequest\Service\BranchRequestService
     */
    public $branchRequestService;

    /**
     * @var \Magestore\BranchRequest\Api\Data\InventoryTransfer\ReceiveInterfaceFactory
     */
    protected $_receiveFactory;

    /**
     * @var \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Receive
     */
    protected $_receiveResource;

    /**
     * @var \Magestore\BranchRequest\Api\Data\InventoryTransfer\DeliveryInterfaceFactory
     */
    protected $_deliveryFactory;

    /**
     * @var \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Delivery
     */
    protected $_deliveryResource;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfigInterface;

    /**
     * AbstractAction constructor.
     *
     * @param Context $context
     */
    public function __construct(
        \Magestore\BranchRequest\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->_eventManager = $context->getEventManager();
        $this->_coreRegistry = $context->getCoreRegistry();
        $this->_resultForwardFactory = $context->getResultForwardFactory();
        $this->_resultPageFactory = $context->getResultPageFactory();
        $this->_resultLayoutFactory = $context->getResultLayoutFactory();
        $this->_logger = $context->getLogger();
        $this->inventoryTransferResource = $context->getInventoryTransferResource();
        $this->inventoryTransferFactory = $context->getInventoryTransferFactory();
        $this->authSession = $context->getAuthSession();
        $this->transferManagement = $context->getTransferManagement();
        $this->_session = $context->getSession();
        $this->filesystem = $context->getFileSystem();
        $this->fileFactory = $context->getFileFactory();
        $this->branchRequestService = $context->getBranchRequestService();
        $this->_receiveFactory = $context->getReceiveFactory();
        $this->_receiveResource = $context->getReceiveResource();
        $this->_deliveryFactory = $context->getDeliveryFactory();
        $this->_deliveryResource = $context->getDeliveryResource();
        $this->scopeConfigInterface = $context->getScopeConfigInterface();
    }
}
