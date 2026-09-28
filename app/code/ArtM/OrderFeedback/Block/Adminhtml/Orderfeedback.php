<?php
namespace ArtM\OrderFeedback\Block\Adminhtml;

class Orderfeedback extends \Magento\Framework\View\Element\Template
{
    
    
    protected $_storeManager;
    protected $_urlInterface;
    protected $orderRepository;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,        
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        array $data = []
    ){
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }
    
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
    
    public function execute()        
    {
        $base_url = $this->_storeManager->getStore()->getBaseUrl();
        $media_url = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }
    public function getResponseSaveUrl()
    {
        return $this->getUrl('orderfeedback/response/save');
    }
}
