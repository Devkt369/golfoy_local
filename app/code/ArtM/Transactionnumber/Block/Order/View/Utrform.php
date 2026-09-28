<?php
namespace ArtM\Transactionnumber\Block\Order\View;

class Utrform extends \Magento\Framework\View\Element\Template
{
    
    
    protected $_storeManager;
    protected $_urlInterface;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,        
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,    
        array $data = []
    ){
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
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
    
    public function getStoreManagerData()
    {    
        echo $this->_storeManager->getStore()->getId() . '<br />';
        
        // by default: URL_TYPE_LINK is returned
        echo $this->_storeManager->getStore()->getBaseUrl() . '<br />';        
        
        echo $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB) . '<br />';
        echo $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK) . '<br />';
        echo $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . '<br />';
        echo $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC) . '<br />';
        
        echo $this->_storeManager->getStore()->getUrl('product/33') . '<br />';
        
        echo $this->_storeManager->getStore()->getCurrentUrl(false) . '<br />';
            
        echo $this->_storeManager->getStore()->getBaseMediaDir() . '<br />';
            
        echo $this->_storeManager->getStore()->getBaseStaticDir() . '<br />';    
    }
    
    /**
     * Prining URLs using URLInterface
     */
    public function getUrlInterfaceData()
    {
        echo $this->_urlInterface->getCurrentUrl() . '<br />';
        
        echo $this->_urlInterface->getUrl() . '<br />';
        
        echo $this->_urlInterface->getUrl('helloworld/general/enabled') . '<br />';
        
        echo $this->_urlInterface->getBaseUrl() . '<br />';
    }
    
    
	function getSubmitUrl(){
        
        return $this->_storeManager->getStore()->getBaseUrl().'transactionnumber/transactionnumber/save';
	}
	
    function getPaymentMethod($orderId){
        
        $order = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Sales\Model\Order')->load( $orderId );
        return $order->getPayment()->getMethod();
	}
    
    public function getTransactionSaveAdminUrl()
    {
        return $this->getUrl('transactionnumber/transactionnumber/save');
    }
    
}
