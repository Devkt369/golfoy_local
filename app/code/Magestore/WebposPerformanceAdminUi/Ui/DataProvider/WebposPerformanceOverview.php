<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformanceAdminUi\Ui\DataProvider;

use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\CollectionFactory
    as CollectionFactory;
use Magestore\WebposPerformance\Model\Source\Adminhtml\TimeRange;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Class WebposPerformanceOverview
 *
 * Data provider for webpos performance overview
 */
class WebposPerformanceOverview extends AbstractDataProvider
{

    /**
     * @var LocationSourceFactory
     */
    protected $locationSourceFactory;

    /**
     * @var int
     */
    protected $defaultLocationId = 0;

    /**
     * ByLocation constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        return array_merge_recursive(
            $data,
            [
                "" => [
                    'time_range' => TimeRange::TODAY
                ]
            ]
        );
    }
}
