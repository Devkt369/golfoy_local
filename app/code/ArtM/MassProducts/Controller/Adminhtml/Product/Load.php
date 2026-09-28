<?php
declare(strict_types=1);

namespace ArtM\MassProducts\Controller\Adminhtml\Product;

use ArtM\MassProducts\Api\ProductLoadInterface;
use ArtM\MassProducts\Model\Store\UserStorePreference;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\StoreManagerInterface;

class Load extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private ProductLoadInterface $productLoad;
    private JsonSerializer $jsonSerializer;
    private UserStorePreference $userStorePreference;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        ProductLoadInterface $productLoad,
        JsonSerializer $jsonSerializer,
        UserStorePreference $userStorePreference,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->productLoad = $productLoad;
        $this->jsonSerializer = $jsonSerializer;
        $this->userStorePreference = $userStorePreference;
        $this->storeManager = $storeManager;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $payload = $this->getRequestPayload();
            $identifier = trim((string)($payload['identifier'] ?? ''));
            $type = trim((string)($payload['type'] ?? 'sku'));
            $storeId = $this->resolveStoreId($payload);

            if ($identifier === '') {
                throw new LocalizedException(__('Identifier is required.'));
            }

            return $result->setData(
                $this->productLoad->load($identifier, $type, $storeId)
            );
        } catch (LocalizedException $e) {
            $this->getResponse()->setHttpResponseCode(400);
            return $result->setData([
                'message' => $e->getMessage(),
                'debug' => [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->getResponse()->setHttpResponseCode(500);
            return $result->setData([
                'message' => $e->getMessage(),
                'debug' => [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ]);
        }
    }

    private function getRequestPayload(): array
    {
        $content = (string)$this->getRequest()->getContent();
        if ($content !== '') {
            $trimmed = ltrim($content);
            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                try {
                    $decoded = $this->jsonSerializer->unserialize($content);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                } catch (\InvalidArgumentException $e) {
                    throw new LocalizedException(__('Invalid JSON payload.'));
                }
            }
        }

        $params = $this->getRequest()->getParams();
        return is_array($params) ? $params : [];
    }

    private function resolveStoreId(array $payload): int
    {
        $requestedStoreId = array_key_exists('store_id', $payload)
            ? (int)$payload['store_id']
            : $this->userStorePreference->getSelectedStoreId();

        if ($requestedStoreId <= 0) {
            $this->userStorePreference->saveSelectedStoreId(0);
            return 0;
        }

        try {
            $storeId = (int)$this->storeManager->getStore($requestedStoreId)->getId();
            $this->userStorePreference->saveSelectedStoreId($storeId);
            return $storeId;
        } catch (\Throwable $e) {
            $this->userStorePreference->saveSelectedStoreId(0);
            return 0;
        }
    }
}
