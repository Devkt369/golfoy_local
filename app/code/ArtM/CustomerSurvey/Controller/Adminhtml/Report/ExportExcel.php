<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Report;

use ArtM\CustomerSurvey\Model\Analytics\ReportService;
use Magento\Backend\App\Action;
use Magento\Framework\App\Response\Http\FileFactory;

class ExportExcel extends Action
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

        $html = '<table border="1"><tr>'
            . '<th>Survey ID</th><th>Name</th><th>Status</th><th>Impressions</th><th>Opens</th><th>Responses</th><th>Completion Rate</th>'
            . '</tr>';

        foreach ($rows as $row) {
            $html .= '<tr>'
                . '<td>' . (int)($row['survey_id'] ?? 0) . '</td>'
                . '<td>' . htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string)($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . (int)($row['impressions'] ?? 0) . '</td>'
                . '<td>' . (int)($row['opens'] ?? 0) . '</td>'
                . '<td>' . (int)($row['responses'] ?? 0) . '</td>'
                . '<td>' . (float)($row['completion_rate'] ?? 0) . '</td>'
                . '</tr>';
        }

        $html .= '</table>';

        return $this->fileFactory->create(
            'customersurvey_report_' . date('Ymd_His') . '.xls',
            $html,
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
            'application/vnd.ms-excel'
        );
    }
}
