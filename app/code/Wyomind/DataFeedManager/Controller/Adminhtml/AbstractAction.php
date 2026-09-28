<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Controller\Adminhtml;

/**
 * Class AbstractAction
 * @package Wyomind\DataFeedManager\Controller\Adminhtml
 */
abstract class AbstractAction extends \Magento\Backend\App\Action
{
    public $_config;

    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;
    /**
     * @var \Magento\Catalog\Model\ProductFactory|null
     */
    protected $_productFactory = null;
    /**
     * @var null
     */
    protected $_fileSystem = null;
    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory|null
     */
    protected $_attributeFactory = null;
    /**
     * @var \Magento\Eav\Model\Entity\TypeFactory|null
     */
    protected $_attributeTypeFactory = null;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection|null
     */
    protected $_attributeOptionValueCollection = null;
    /**
     * @var \Magento\Framework\View\Result\PageFactory|null
     */
    protected $_resultPageFactory = null;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList|null
     */
    protected $_directoryList = null;
    /**
     * @var null|\Wyomind\DataFeedManager\Model\Product\Collection
     */
    protected $_productCollection = null;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory|null
     */
    protected $_resultRedirectFactory = null;
    /**
     * @var null|\Wyomind\DataFeedManager\Helper\Attributes
     */
    protected $_attributesHelper = null;

    /**
     * @var null|\Wyomind\DataFeedManager\Helper\Data
     */
    public $dfmHelper = null;
    /**
     * @var null|\Wyomind\DataFeedManager\Model\Feeds
     */
    public $dfmModel = null;
    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface|null
     */
    public $directoryRead = null;
    /**
     * @var null|\Wyomind\Framework\Helper\Heartbeat
     */
    public $framework = null;
    /**
     * @var \Wyomind\Framework\Helper\Download
     */
    public $frameworkExport;
    /**
     * @var \Magento\Catalog\Api\ProductAttributeOptionManagementInterface|null
     */
    public $productAttributeRepository = null;
    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface|null
     */
    public $attributeRepository = null;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface|null
     */
    public $productRepository = null;
    /**
     * @var null|\Wyomind\DataFeedManager\Helper\Parser
     */
    public $parserHelper = null;
    /**
     * @var \Magento\Framework\Message\ManagerInterface|null
     */
    public $messageManager = null;
    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory|null
     */
    public $resultForwardFactory = null;
    /**
     * @var null|\Wyomind\DataFeedManager\Model\ResourceModel\Variables\CollectionFactory
     */
    public $variablesCollectionFactory = null;

    /**
     * @var string
     */
    public $title = "";
    /**
     * @var string
     */
    public $breadcrumbOne = "";
    /**
     * @var string
     */
    public $breadcrumbTwo = "";
    /**
     * @var string
     */
    public $menu = "";
    /**
     * @var string
     */
    public $model = "";
    /**
     * @var string
     */
    public $errorDoesntExist = "";
    /**
     * @var string
     */
    public $successDelete = "";
    /**
     * @var string
     */
    public $msgModify = "";
    /**
     * @var string
     */
    public $msgNew = "";
    /**
     * @var string
     */
    public $registryName = "";

    /**
     * AbstractAction constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Config\Model\ResourceModel\Config $config
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Module\Dir\Reader $directoryReader
     * @param \Wyomind\DataFeedManager\Model\Product\Collection $productCollection
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection $attributeOptionValueCollection
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Wyomind\Framework\Helper\Heartbeat $framework
     * @param \Wyomind\Framework\Helper\Download $frameworkExport
     * @param \Wyomind\DataFeedManager\Helper\Data $dfmHelper
     * @param \Wyomind\DataFeedManager\Model\Feeds $dfmModel
     * @param \Magento\Eav\Model\Entity\TypeFactory $attributeTypeFactory
     * @param \Wyomind\DataFeedManager\Helper\Parser $parserHelper
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Api\ProductAttributeOptionManagementInterface $productAttributeRepository
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory $directoryRead
     * @param \Wyomind\DataFeedManager\Model\ResourceModel\Variables\CollectionFactory $variablesCollectionFactory
     * @param \Wyomind\DataFeedManager\Helper\Attributes $attributesHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Config\Model\ResourceModel\Config $config,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Module\Dir\Reader $directoryReader,
        \Wyomind\DataFeedManager\Model\Product\Collection $productCollection,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection $attributeOptionValueCollection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Wyomind\Framework\Helper\Heartbeat $framework,
        \Wyomind\Framework\Helper\Download $frameworkExport,
        \Wyomind\DataFeedManager\Helper\Data $dfmHelper,
        \Wyomind\DataFeedManager\Model\Feeds $dfmModel,
        \Magento\Eav\Model\Entity\TypeFactory $attributeTypeFactory,
        \Wyomind\DataFeedManager\Helper\Parser $parserHelper,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Api\ProductAttributeOptionManagementInterface $productAttributeRepository,
        \Magento\Framework\Filesystem\Directory\ReadFactory $directoryRead,
        \Wyomind\DataFeedManager\Model\ResourceModel\Variables\CollectionFactory $variablesCollectionFactory,
        \Wyomind\DataFeedManager\Helper\Attributes $attributesHelper
    ) {
    
        $this->_coreRegistry = $coreRegistry;
        $this->_attributeFactory = $attributeFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_config = $config;
        $this->_directoryList = $directoryList;
        $this->_productCollection = $productCollection;
        $this->_attributeOptionValueCollection = $attributeOptionValueCollection;
        $this->_productFactory = $productFactory;
        $this->_attributeTypeFactory = $attributeTypeFactory;
        $this->_resultRedirectFactory = $context->getResultRedirectFactory();
        $this->_attributesHelper = $attributesHelper;

        $this->dfmHelper = $dfmHelper;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->dfmModel = $dfmModel;

        $directory = $directoryReader->getModuleDir('', 'Wyomind_DataFeedManager');
        if (file_exists($directory)) {
            $this->directoryRead = $directoryRead->create($directory);
        } else {
            return [];
        }

        $this->framework = $framework;
        $this->frameworkExport = $frameworkExport;
        $this->parserHelper = $parserHelper;
        $this->attributeRepository = $attributeRepository;
        $this->productRepository = $productRepository;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->messageManager = $context->getMessageManager();
        $this->variablesCollectionFactory = $variablesCollectionFactory;
        parent::__construct($context);
    }

    /**
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Wyomind_DataFeedManager::' . $this->menu);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function delete()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $model = $this->_objectManager->create($this->model);
                $model->setId($id);
                $model->delete();
                $this->messageManager->addSuccess(__($this->successDelete));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__($this->errorDoesntExist));
        }

        $return = $this->_resultRedirectFactory->create()->setPath('datafeedmanager/' . $this->menu . '/index');
        return $return;
    }

    /**
     * Execute Edit action
     * @return type
     */
    public function edit()
    {
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu("Magento_Catalog::catalog");
        $resultPage->addBreadcrumb(__($this->breadcrumbOne), __($this->breadcrumbOne));
        $resultPage->addBreadcrumb(__($this->breadcrumbTwo), __($this->breadcrumbTwo));

        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create($this->model);

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__($this->errorDoesntExist));
                return $this->_resultRedirectFactory->create()->setPath('datafeedmanager/' . $this->menu . '/index');
            }
        }
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? (__($this->msgModify)) : __($this->msgNew));

        $this->_coreRegistry->register($this->registryName, $model);

        return $resultPage;
    }

    /**
     * Execute new action
     */
    public function newAction()
    {
        return $this->resultForwardFactory->create()->forward("edit");
    }

    /**
     * Execute index action
     */
    public function index()
    {
        $this->framework->checkHeartbeat();
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu("Magento_Catalog::" . $this->menu);
        $resultPage->getConfig()->getTitle()->prepend(__($this->title));
        $resultPage->addBreadcrumb($this->breadcrumbOne, __($this->breadcrumbOne));
        $resultPage->addBreadcrumb($this->breadcrumbTwo, __($this->breadcrumbTwo));
        return $resultPage;
    }
}
