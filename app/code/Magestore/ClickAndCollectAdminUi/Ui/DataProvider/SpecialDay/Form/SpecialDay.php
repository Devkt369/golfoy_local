<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Ui\DataProvider\SpecialDay\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;
use Magestore\ClickAndCollectApi\Api\DataProvider\GetSpecialDayByIdInterface;
use Magestore\ClickAndCollect\Model\ResourceModel\SpecialDay\CollectionFactory;

/**
 * Class SpecialDay
 *
 * Data provider for create form
 */
class SpecialDay extends AbstractDataProvider
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
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var GetSpecialDayByIdInterface
     */
    protected $getSpecialDayById;

    /**
     * SpecialDay constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param RequestInterface $request
     * @param PoolInterface $pool
     * @param GetSpecialDayByIdInterface $getSpecialDayById
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        RequestInterface $request,
        PoolInterface $pool,
        GetSpecialDayByIdInterface $getSpecialDayById,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->request = $request;
        $this->pool = $pool;
        $this->getSpecialDayById = $getSpecialDayById;
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
            if ((int) $this->request->getParam(SpecialDayInterface::SPECIAL_DAY_ID)) {
                $specialDay = $this->getSpecialDayById->execute(
                    (int)$this->request->getParam(SpecialDayInterface::SPECIAL_DAY_ID)
                );
                if ($specialDay && $specialDay->getSpecialDayId()) {
                    $specialDayData = $specialDay->getData();
                    $specialDayData[SpecialDayInterface::TIME_OPEN . '_group'] =
                        $this->splitHourAndMin(
                            SpecialDayInterface::TIME_OPEN,
                            $specialDay->getTimeOpen()
                        );
                    $specialDayData[SpecialDayInterface::TIME_CLOSE . '_group'] =
                        $this->splitHourAndMin(
                            SpecialDayInterface::TIME_CLOSE,
                            $specialDay->getTimeClose()
                        );
                    $this->loadedData[$specialDay->getSpecialDayId()]['general_information'] = $specialDayData;
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
     * Split hour and min
     *
     * @param string $key
     * @param string $time
     * @return array
     */
    public function splitHourAndMin(string $key, string $time)
    {
        if (!$time) {
            return null;
        }
        $result = [];
        $minHourArray = explode(':', (string)$time);
        if (isset($minHourArray[0])) {
            $result[$key . '_' . 'hour'] = $minHourArray[0];
        }
        if (isset($minHourArray[1])) {
            $result[$key . '_' . 'min'] = $minHourArray[1];
        }
        return $result;
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
