<?php
namespace ArtM\SFMC\Observer\Catalog;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use ArtM\SFMC\Model\SfmcProductFailedFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use ArtM\SFMC\Helper\Data;
// use ArtM\GraphqlCache\Services\Indexer\ProductPricesHelper;

class ProductSaveBefore implements ObserverInterface
{

    protected $_sfmcProductFailedFactory;
    protected $productRepository;
    protected $productLogger;
    protected $_helperData;
    // protected $productPricesHelper;
    

    /**
     * Undocumented function
     *
     * @param SfmcProductFailedFactory $_sfmcProductFailedFactory
     * @param ProductRepositoryInterface $productRepository
     * @param \ArtM\SFMC\Logger\ProductLogger $productLogger
     * @param Data $helperData
     * @param ProductPricesHelper $productPricesHelper
     */ 
    public function __construct(
        SfmcProductFailedFactory $_sfmcProductFailedFactory,
        ProductRepositoryInterface $productRepository,
        \ArtM\SFMC\Logger\ProductLogger $productLogger,
        Data $_helperData,
        // ProductPricesHelper $productPricesHelper
        ){
        $this->_sfmcProductFailedFactory = $_sfmcProductFailedFactory;
        $this->productRepository = $productRepository;
        $this->productLogger = $productLogger;
        $this->_helperData = $_helperData;
        // $this->productPricesHelper = $productPricesHelper;
    }
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $product = $product->setData('sfmcsyncproduct', '0');
        try{
            if($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
                // $simpleProducts = $this->productPricesHelper->getAssociatedSimpleProducts($product);
                // $lowestTierPrices = $this->productPricesHelper->getLowestTierPricesFromSimples($simpleProducts);
                // $this->productPricesHelper->updateConfigurableTierPrices($product, $lowestTierPrices);
            }

            $sfmcProductFailedModel = $this->_sfmcProductFailedFactory->create();
            $sfmcProductFailedModel = $sfmcProductFailedModel->getCollection()->addFieldToFilter('product_id', ['eq' => $product->getId()]);

            if(!empty($sfmcProductFailedModel->getData())){
                foreach($sfmcProductFailedModel as $sfmcProductFailedDelete){
                    $sfmcProductFailedDelete->delete();
                }
            }
            return;

        }catch(\Exception $e){

            return ;
        }
    }
}