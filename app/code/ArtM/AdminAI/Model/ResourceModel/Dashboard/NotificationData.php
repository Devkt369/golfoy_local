<?php
namespace ArtM\AdminAI\Model\ResourceModel\Dashboard;

use Magento\Framework\App\ResourceConnection;

class NotificationData
{
    /** @var ResourceConnection */
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function fetch(array $filters): array
    {
        $conn = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_adminai_notification_queue');

        $summarySelect = $conn->select()
            ->from(
                ['n' => $table],
                [
                    'unread_notifications' => 'COALESCE(SUM(CASE WHEN n.status = "new" THEN 1 ELSE 0 END), 0)',
                ]
            )
            ->where('DATE(n.created_at) >= ?', (string)$filters['from_date'])
            ->where('DATE(n.created_at) <= ?', (string)$filters['to_date']);

        $gridSelect = $conn->select()
            ->from(
                ['n' => $table],
                [
                    'notification_id' => 'n.notification_id',
                    'notification_type' => 'n.notification_type',
                    'severity' => 'n.severity',
                    'title' => 'n.title',
                    'status' => 'n.status',
                    'created_at' => 'n.created_at',
                ]
            )
            ->where('DATE(n.created_at) >= ?', (string)$filters['from_date'])
            ->where('DATE(n.created_at) <= ?', (string)$filters['to_date'])
            ->order('n.created_at DESC');

        $severitySelect = $conn->select()
            ->from(
                ['n' => $table],
                [
                    'severity' => 'n.severity',
                    'severity_count' => 'COUNT(*)',
                ]
            )
            ->where('DATE(n.created_at) >= ?', (string)$filters['from_date'])
            ->where('DATE(n.created_at) <= ?', (string)$filters['to_date'])
            ->group('n.severity')
            ->order('severity_count DESC');

        $summary = (array)$conn->fetchRow($summarySelect);
        $grid = $conn->fetchAll($gridSelect);
        $severityRows = $conn->fetchAll($severitySelect);

        $labels = [];
        $values = [];
        $severitySummary = [];
        foreach ($severityRows as $row) {
            $severity = (string)($row['severity'] ?? 'info');
            $count = (int)($row['severity_count'] ?? 0);
            $labels[] = $severity;
            $values[] = $count;
            $severitySummary[] = [
                'severity' => strtoupper($severity),
                'count' => $count,
            ];
        }

        $unread = (int)($summary['unread_notifications'] ?? 0);
        $recommendations = [];
        if ($unread > 0) {
            $recommendations[] = 'Prioritize high severity notifications in the next review cycle.';
            $recommendations[] = 'Assign owners for unresolved alerts to reduce unread backlog.';
        } else {
            $recommendations[] = 'No unread notifications. Keep daily monitoring cadence.';
        }
        if (!empty($severitySummary)) {
            $recommendations[] = 'Most common severity in selected range: ' . $severitySummary[0]['severity'] . '.';
        }

        return [
            'summary' => [
                'section' => 'notifications',
                'from_date' => (string)$filters['from_date'],
                'to_date' => (string)$filters['to_date'],
                'unread_notifications' => $unread,
            ],
            'grid' => is_array($grid) ? $grid : [],
            'charts' => [
                'severity_distribution' => [
                    'labels' => $labels,
                    'values' => $values,
                ],
            ],
            'widgets' => [
                'action_recommendations' => $recommendations,
                'severity_summary' => $severitySummary,
            ],
        ];
    }
}