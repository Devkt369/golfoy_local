<?php
namespace ArtM\CustomerLastOrder\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class UpdateLastOrderDate implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    protected $timezone;

    /**
     * Constructor
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TimezoneInterface $timezone,
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->timezone = $timezone;
    }

    /**
     * Execute the observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            date_default_timezone_set('Asia/Kolkata');
            $order = $observer->getEvent()->getOrder();
            $customerId = $order->getCustomerId();
            // if ($customerId) {
            //     $connection = $this->resourceConnection->getConnection();
            //     $tableName = $this->resourceConnection->getTableName('customer_grid_flat');
            //     $data = [
            //         'last_order_date' => $order->getCreatedAt(),
            //     ];
            //     $where = ['entity_id = ?' => $customerId];
            //     $connection->update($tableName, $data, $where);
            // }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Error updating last order date: %1', $e->getMessage()));
        }
    }
}
