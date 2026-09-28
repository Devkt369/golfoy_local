<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Report;

use ArtM\CustomerSurvey\Model\Analytics\ReportService;
use Magento\Backend\App\Action;
use Magento\Framework\App\Response\Http\FileFactory;

class ExportCsv extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::reports_export';

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var ReportService
     */
    private $reportService;

    public function __construct(
        Action\Context $context,
        FileFactory $fileFactory,
        ReportService $reportService
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->reportService = $reportService;
    }

    public function execute()
    {
        $fromDate = (string)$this->getRequest()->getParam('from_date', gmdate('Y-m-01'));
        $toDate = (string)$this->getRequest()->getParam('to_date', gmdate('Y-m-d'));

        $listing = $this->reportService->getSurveyListing($fromDate, $toDate, 1, 10000);
        $rows = $listing['items'] ?? [];

        $content = [];
        $content[] = [
            'survey_id',
            'name',
            'status',
            'impressions',
            'opens',
            'responses',
            'completion_rate'
        ];

        foreach ($rows as $row) {
            $content[] = [
                (string)($row['survey_id'] ?? ''),
                (string)($row['name'] ?? ''),
                (string)($row['status'] ?? ''),
                (string)($row['impressions'] ?? 0),
                (string)($row['opens'] ?? 0),
                (string)($row['responses'] ?? 0),
                (string)($row['completion_rate'] ?? 0),
            ];
        }

        $csv = '';
        foreach ($content as $line) {
            $escaped = array_map(static function (string $field): string {
                $field = str_replace('"', '""', $field);
                return '"' . $field . '"';
            }, $line);
            $csv .= implode(',', $escaped) . "\n";
        }

        return $this->fileFactory->create(
            'customersurvey_report_' . date('Ymd_His') . '.csv',
            $csv,
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
            'text/csv'
        );
    }
}
