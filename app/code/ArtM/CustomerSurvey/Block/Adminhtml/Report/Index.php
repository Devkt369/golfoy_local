<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Block\Adminhtml\Report;

use ArtM\CustomerSurvey\Model\Analytics\ReportService;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;

class Index extends Template
{
    /**
     * @var ReportService
     */
    private $reportService;

    /**
     * @var array<string, mixed>|null
     */
    private $listingData;

    /**
     * @var array<string, mixed>|null
     */
    private $detailData;

    public function __construct(
        Context $context,
        ReportService $reportService,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->reportService = $reportService;
    }

    public function getFromDate(): string
    {
        return (string)$this->getRequest()->getParam('from_date', gmdate('Y-m-01'));
    }

    public function getToDate(): string
    {
        return (string)$this->getRequest()->getParam('to_date', gmdate('Y-m-d'));
    }

    public function getSelectedSurveyId(): int
    {
        return max(0, (int)$this->getRequest()->getParam('survey_id', 0));
    }

    public function getResponsePage(): int
    {
        return max(1, (int)$this->getRequest()->getParam('response_page', 1));
    }

    public function getResponseLimit(): int
    {
        return max(1, min(100, (int)$this->getRequest()->getParam('response_limit', 20)));
    }

    /**
     * @return array<string, mixed>
     */
    public function getListingData(): array
    {
        if ($this->listingData !== null) {
            return $this->listingData;
        }

        $page = max(1, (int)$this->getRequest()->getParam('p', 1));
        $pageSize = max(1, min(200, (int)$this->getRequest()->getParam('limit', 20)));

        $this->listingData = $this->reportService->getSurveyListing(
            $this->getFromDate(),
            $this->getToDate(),
            $page,
            $pageSize
        );

        return $this->listingData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetailData(): array
    {
        if ($this->detailData !== null) {
            return $this->detailData;
        }

        $surveyId = $this->getSelectedSurveyId();
        if ($surveyId <= 0) {
            $this->detailData = [];
            return $this->detailData;
        }

        try {
            $this->detailData = $this->reportService->getSurveyDetail(
                $surveyId,
                $this->getFromDate(),
                $this->getToDate(),
                $this->getResponsePage(),
                $this->getResponseLimit()
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->detailData = [];
        }

        return $this->detailData;
    }

    public function getExportCsvUrl(): string
    {
        return $this->getUrl('customersurvey/report/exportCsv', [
            'from_date' => $this->getFromDate(),
            'to_date' => $this->getToDate(),
        ]);
    }

    public function getExportExcelUrl(): string
    {
        return $this->getUrl('customersurvey/report/exportExcel', [
            'from_date' => $this->getFromDate(),
            'to_date' => $this->getToDate(),
        ]);
    }
}
