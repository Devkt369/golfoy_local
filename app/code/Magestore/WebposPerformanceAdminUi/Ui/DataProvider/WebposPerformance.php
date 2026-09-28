<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformanceAdminUi\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\CollectionFactory
    as CollectionFactory;

/**
 * Class WebposPerformance
 *
 * Used for performance filter
 */
class WebposPerformance extends AbstractDataProvider
{
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
}
