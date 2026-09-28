<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel\Survey\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface;

class Collection extends SearchResult
{
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        EventManagerInterface $eventManager,
        $mainTable = 'artm_customersurvey_survey',
        $resourceModel = \ArtM\CustomerSurvey\Model\ResourceModel\Survey::class
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()
            ->joinLeft(
                ['sq' => $this->getTable('artm_customersurvey_survey_question')],
                'main_table.survey_id = sq.survey_id',
                []
            )
            ->joinLeft(
                ['sl' => $this->getTable('artm_customersurvey_survey_location')],
                'main_table.survey_id = sl.survey_id',
                []
            )
            ->columns([
                'question_count' => new \Zend_Db_Expr('COUNT(DISTINCT sq.question_id)'),
                'location_count' => new \Zend_Db_Expr('COUNT(DISTINCT sl.location_id)'),
                'timeline' => new \Zend_Db_Expr("CONCAT(IFNULL(DATE_FORMAT(main_table.start_date, '%Y-%m-%d'), '-'), ' to ', IFNULL(DATE_FORMAT(main_table.end_date, '%Y-%m-%d'), '-'))"),
            ])
            ->group('main_table.survey_id');

        return $this;
    }
}
