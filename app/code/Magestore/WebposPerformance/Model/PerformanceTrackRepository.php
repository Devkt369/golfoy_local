<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackInterface;
use Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackInterfaceFactory;
use Magestore\WebposPerformanceApi\Api\PerformanceTrackRepositoryInterface;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack as PerformanceTrackResource;

/**
 * Class PerformanceTrackRepository
 *
 * Used for Performance track repository
 */
class PerformanceTrackRepository implements PerformanceTrackRepositoryInterface
{
    /**
     * @var PerformanceTrackResource
     */
    protected $performanceTrackResourceModel;

    /**
     * @var  PerformanceTrackInterfaceFactory
     */
    protected $performanceTrackFactory;

    /**
     * PerformanceTrackRepository constructor.
     *
     * @param PerformanceTrackResource $performanceTrackResourceModel
     * @param PerformanceTrackInterfaceFactory $performanceTrackFactory
     */
    public function __construct(
        PerformanceTrackResource $performanceTrackResourceModel,
        PerformanceTrackInterfaceFactory $performanceTrackFactory
    ) {
        $this->performanceTrackFactory = $performanceTrackFactory;
        $this->performanceTrackResourceModel = $performanceTrackResourceModel;
    }

    /**
     * Save performance.
     *
     * @param PerformanceTrackInterface[] $performanceTrack
     * @return boolean
     * @throws LocalizedException
     */
    public function save(array $performanceTrack)
    {
        $dataToInsert = [];
        foreach ($performanceTrack as $record) {
            $dataToInsert[] = $record->getData();
        }

        try {
            /* Bulk insert */
            $this->performanceTrackResourceModel->getConnection()->insertMultiple(
                $this->performanceTrackResourceModel->getMainTable(),
                $dataToInsert
            );
        } catch (LocalizedException $exception) {
            return false;
        }

        return true;
    }
}
