<?php
declare(strict_types=1);

namespace ArtM\Common\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CacheInterface;
use ArtM\Common\Block\Productpriceblock;

class Productprices extends Action
{
    private JsonFactory $resultJsonFactory;
    private CacheInterface $cache;
    private Productpriceblock $priceBlock;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CacheInterface $cache,
        Productpriceblock $priceBlock
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cache = $cache;
        $this->priceBlock = $priceBlock;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $products = json_decode($this->getRequest()->getParam('products', '[]'), true);

        if (empty($products)) {
            return $result->setData([]);
        }

        // 🔹 Get ALL size-roll data ONCE (already cached in block)
        $sizerollMap = $this->priceBlock->getAllSizerollMap();

        $response = [];

        foreach ($products as $item) {
            $pid = (int)($item['pid'] ?? 0);
            if (!$pid) {
                continue;
            }

            $response[$pid] = [
                'sizeroll' => $sizerollMap[$pid] ?? '',
                // you can add more raw data later
            ];
        }

        return $result->setData($response);
    }
}