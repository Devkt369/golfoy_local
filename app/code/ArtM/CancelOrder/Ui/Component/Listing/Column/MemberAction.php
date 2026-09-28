<?php 
namespace ArtM\CancelOrder\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class MemberAction extends Column
{
	protected $url;
	public function __construct(ContextInterface $context,
		UiComponentFactory $UiComponentFactory,
		UrlInterface $url,
		array $components= [],
		array $data= []
	)
	{
		$this->url = $url;
		parent::__construct($context,$UiComponentFactory,$components,$data);
	}
	public function prepareDataSource(array $dataSource)
	{
		if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['order_id'])) {

                    $url = $this->url->getUrl('sales/order/view', ['order_id' => $item['order_id']]);

             $link = "<a target='_blank' href='" . $url . "' > View Order</a>";
                    $item[$this->getData('name')] = $link;
                }
            }
        }
         return $dataSource;

	}

}