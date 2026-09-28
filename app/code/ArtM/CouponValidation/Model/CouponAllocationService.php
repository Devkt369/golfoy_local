<?php
namespace ArtM\CouponValidation\Model;

use Magento\Framework\App\ResourceConnection;

/**
 * Service to allocate coupons from salesrule_coupon into artm_couponvalidation
 */
class CouponAllocationService
{
    /** @var ResourceConnection */
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Allocate coupons
     *
     * @param int $ruleId
     * @param int $quantity
     * @param string $expiryDate MySQL datetime
     * @return array allocated coupon codes
     * @throws \Exception
     */
    public function allocate(int $ruleId, int $quantity, string $expiryDate): array
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero');
        }

        $conn = $this->resource->getConnection();
        $salesruleTable = $this->resource->getTableName('salesrule_coupon');
        $allocTable = $this->resource->getTableName('artm_couponvalidation');

        $select = $conn->select()
            ->from($salesruleTable, ['code'])
            ->where('rule_id = ?', $ruleId)
            ->where('code NOT IN (SELECT coupon_code COLLATE utf8mb4_general_ci FROM ' . $conn->quoteIdentifier($allocTable) . ')')
            ->limit($quantity);

        $available = $conn->fetchCol($select);
        $availableCount = count($available);

        if ($availableCount < $quantity) {
            throw new \Exception(sprintf('Only %d coupons available. Requested %d.', $availableCount, $quantity));
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [];
        foreach ($available as $code) {
            $insertData[] = [
                'coupon_code' => $code,
                'rule_id' => $ruleId,
                'expiry_date' => $expiryDate,
                'status' => 0,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        $conn->beginTransaction();
        try {
            foreach (array_chunk($insertData, 1000) as $chunk) {
                $conn->insertMultiple($allocTable, $chunk);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }

        return array_values($available);
    }
}
