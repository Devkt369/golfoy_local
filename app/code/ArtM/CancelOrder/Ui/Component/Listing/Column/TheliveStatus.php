<?php 
namespace ArtM\CancelOrder\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;

class TheliveStatus extends Column
{
	protected $url;
	protected $orderFactory;
	public function __construct(ContextInterface $context,
		UiComponentFactory $UiComponentFactory,
		UrlInterface $url,		
		\Magento\Sales\Model\OrderFactory $orderFactory,
		array $components= [],
		array $data= []
	)
	{
		
		parent::__construct($context,$UiComponentFactory,$components,$data);
		$this->orderFactory = $orderFactory;
		$this->url = $url;
	}
	public function prepareDataSource(array $dataSource)
	{
		 if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['order_id'])) {


                	 $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $order = $objectManager->create('\Magento\Sales\Model\Order')->load($item['order_id']);
                        $state = $order->getStatus();
                        //echo $state;
                        //exit();

                  //  $viewUrlPath = $this->getData('config/viewUrlPath') ?: '#';
                  //  $urlEntityParamName = $this->getData('config/urlEntityParamName') ?: 'order_id';
                    $item[$this->getData('name')] = $state;
                }
            }
        }

        return $dataSource;

	}

}