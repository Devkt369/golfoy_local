<?php
namespace WeserveCommerce\AjaxSearch\Controller\Search;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use WeserveCommerce\AjaxSearch\Api\SearchServiceInterface;

class Autocomplete extends Action implements HttpGetActionInterface
{
    /**
     * @var SearchServiceInterface
     */
    private $searchService;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    public function __construct(
        Context $context,
        SearchServiceInterface $searchService,
        StoreManagerInterface $storeManager,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->searchService = $searchService;
        $this->storeManager = $storeManager;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $request = $this->getRequest();
        $query = (string)$request->getParam('q', '');
        $limitParam = $request->getParam('limit');
        $limit = is_numeric($limitParam) ? (int)$limitParam : null;
        $pageParam = $request->getParam('page');
        $page = is_numeric($pageParam) ? (int)$pageParam : null;
        $storeId = (int)$this->storeManager->getStore()->getId();

        try {
            if ($page !== null && $page > 0) {
                $data = $this->searchService->search($query, $storeId, [], $page, $limit);
            } else {
                $data = $this->searchService->autocomplete($query, $storeId, $limit);
            }
        } catch (\Throwable $e) {
            $data = [
                'query' => $query,
                'total' => 0,
                'products' => [],
                'error' => $e->getMessage()
            ];
        }

        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $result->setData($data);
        return $result;
    }
}
