<?php
declare(strict_types=1);

namespace ArtM\MassProducts\Model\Store;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\ResourceConnection;

class UserStorePreference
{
    private const TABLE_NAME = 'artm_massproducts_user_store_pref';

    private ResourceConnection $resource;
    private AuthSession $authSession;

    public function __construct(
        ResourceConnection $resource,
        AuthSession $authSession
    ) {
        $this->resource = $resource;
        $this->authSession = $authSession;
    }

    public function getSelectedStoreId(): int
    {
        $userId = $this->getCurrentUserId();
        if ($userId <= 0) {
            return 0;
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName(self::TABLE_NAME);
        if (!$connection->isTableExists($table)) {
            return 0;
        }

        $storeId = $connection->fetchOne(
            $connection->select()
                ->from($table, ['selected_store_id'])
                ->where('user_id = ?', $userId)
                ->limit(1)
        );

        return $storeId !== false ? max(0, (int)$storeId) : 0;
    }

    public function saveSelectedStoreId(int $storeId): void
    {
        $userId = $this->getCurrentUserId();
        if ($userId <= 0) {
            return;
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName(self::TABLE_NAME);
        if (!$connection->isTableExists($table)) {
            return;
        }

        $connection->insertOnDuplicate(
            $table,
            [
                'user_id' => $userId,
                'selected_store_id' => max(0, $storeId),
            ],
            ['selected_store_id']
        );
    }

    private function getCurrentUserId(): int
    {
        $user = $this->authSession->getUser();
        if (!$user || !(int)$user->getId()) {
            return 0;
        }

        return (int)$user->getId();
    }
}
