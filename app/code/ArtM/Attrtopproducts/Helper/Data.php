<?php
namespace ArtM\Attrtopproducts\Helper;

use Exception;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $productRepository;
    private $prHelper;
    public function __construct(    
        \Magento\Framework\App\Helper\Context $context,    
        \Magento\Catalog\Model\ProductRepository $productRepository,		
        \Magento\Framework\Pricing\Helper\Data $prHelper,
		array $data = []
    ){		
		$this->productRepository = $productRepository;
        $this->prHelper = $prHelper;
		parent::__construct($context);
    }    
    public function catTopProductsHtml($prids)
    {
        $return = '';

        $prds = array_filter(array_map('trim', explode(',', $prids)));

        if (!empty($prds)) {

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $productCollectionFactory = $objectManager->get(
                \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class
            );

            $collection = $productCollectionFactory->create();

            $collection->addIdFilter($prds)
                ->addAttributeToSelect([
                    'name',
                    'url_key',
                    'price',
                    'special_price'
                ])
                ->addFinalPrice()
                ->addAttributeToFilter(
                    'status',
                    ['eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED]
                );

            // Preserve the same order as passed in $prids
            $collection->getSelect()->order(
                new \Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', $prds) . ')')
            );

            $i = 1;
// print_r(implode(',', $prds));die;
            foreach ($collection as $product) {

                if (!$product->isSalable()) {
                    continue;
                }

                $productUrl = htmlspecialchars($product->getProductUrl(), ENT_QUOTES, 'UTF-8');
                $productName = htmlspecialchars($product->getName(), ENT_QUOTES, 'UTF-8');

                $productname = "<a href='{$productUrl}'>{$productName}</a>";

                // Final/Special price
                $finalPrice = $product->getPriceInfo()
                    ->getPrice('final_price')
                    ->getValue();

                $formattedPrice = $this->prHelper->currency(
                    $finalPrice,
                    true,
                    false
                );

                $return .= "<tr data-product-id='{$product->getId()}'>
                                <td>{$i}</td>
                                <td>{$productname}</td>
                                <td>{$formattedPrice}</td>
                            </tr>";

                $i++;
            }
        }
        return $return;
    }
}