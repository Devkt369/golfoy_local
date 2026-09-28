<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Ui\DataProvider\Schedule\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magestore\ClickAndCollect\Model\ResourceModel\Schedule\CollectionFactory;
use Magestore\ClickAndCollectApi\Api\DataProvider\GetScheduleByIdInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Schedule
 *
 * Data provider for create form
 */
class Schedule extends AbstractDataProvider
{
    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * @var array
     */
    protected $loadedData = [];

    /**
     * @var GetScheduleByIdInterface
     */
    protected $getScheduleById;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Schedule constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param GetScheduleByIdInterface $getScheduleById
     * @param RequestInterface $request
     * @param PoolInterface $pool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        GetScheduleByIdInterface $getScheduleById,
        RequestInterface $request,
        PoolInterface $pool,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->getScheduleById = $getScheduleById;
        $this->request = $request;
        $this->pool = $pool;
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        try {
            if ((int) $this->request->getParam('schedule_id')) {
                $schedule = $this->getScheduleById->execute((int)$this->request->getParam('schedule_id'));
                if ($schedule && $schedule->getScheduleId()) {
                    $this->loadedData[$schedule->getScheduleId()]['schedule_information_section']
                        = $this->splitHourAndMin($schedule->getData());
                }
            }
        } catch (NoSuchEntityException $exception) {
            return [];
        }

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $this->loadedData = $modifier->modifyData($this->loadedData);
        }

        return $this->loadedData;
    }

    /**
     * Split hour and min for schedule data
     *
     * @param array $scheduleData
     * @return array
     */
    public function splitHourAndMin(array $scheduleData)
    {
        foreach ($scheduleData as $key => $value) {
            if (strpos($key, '_open') !== false || strpos($key, '_close') !== false) {
                $time = $value;
                if ($time) {
                    $minHourArray = explode(':', (string)$time);
                    if (isset($minHourArray[0])) {
                        $scheduleData[$key . '_' . 'group'][$key . '_' . 'hour'] = $minHourArray[0];
                    }
                    if (isset($minHourArray[1])) {
                        $scheduleData[$key . '_' . 'group'][$key . '_' . 'min'] = $minHourArray[1];
                    }
                }
            }
        }
        return $scheduleData;
    }

    /**
     * @inheritDoc
     */
    public function getMeta(): array
    {
        $meta = parent::getMeta();

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }
}
