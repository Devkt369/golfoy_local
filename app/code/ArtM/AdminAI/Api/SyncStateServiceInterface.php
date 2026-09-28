<?php
namespace ArtM\AdminAI\Api;

interface SyncStateServiceInterface
{
    /**
     * Read current sync state for a sync code.
     *
     * @param string $syncCode
     * @return array{
     *     sync_code:string,
     *     last_synced_at:?string,
     *     last_entity_id:?int,
     *     status:string,
     *     message:?string,
     *     updated_at:?string
     * }
     */
    public function getState(string $syncCode): array;

    /**
     * Persist sync watermark and optional state fields.
     *
     * @param string $syncCode
     * @param string|null $lastSyncedAt Format: Y-m-d H:i:s (UTC)
     * @param int|null $lastEntityId
     * @param string $status
     * @param string|null $message
     * @return void
     */
    public function saveState(
        string $syncCode,
        ?string $lastSyncedAt,
        ?int $lastEntityId,
        string $status = 'idle',
        ?string $message = null
    ): void;

    /**
     * Update only sync status and message.
     *
     * @param string $syncCode
     * @param string $status
     * @param string|null $message
     * @return void
     */
    public function setStatus(string $syncCode, string $status, ?string $message = null): void;
}
