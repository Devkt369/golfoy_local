<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Appadmin\Ui\DataProvider\Staff\Form;

use Magento\Framework\Registry;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magestore\Appadmin\Model\ResourceModel\Staff\Staff\CollectionFactory;
use Magestore\Appadmin\Model\ResourceModel\Staff\Staff\Collection;

/**
 * Form - Staff
 */
class Staff extends AbstractDataProvider
{

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * Staff constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param PoolInterface $pool
     * @param Registry $registry
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        PoolInterface $pool,
        Registry $registry,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->pool = $pool;
        $this->_coreRegistry = $registry;
        $this->collection = $collectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        $staff = $this->_coreRegistry->registry('current_staff');
        if ($staff && $staff->getId()) {
            $data = $staff->getData();

//            if ($data['pos_ids']) {
//                $data['pos_ids'] = explode(',', $data['pos_ids']);
//            }

            if ($data['location_ids']) {
                $data['location_ids'] = explode(',', (string)$data['location_ids']);
            }
            if (isset($data['password'])) {
                unset($data['password']);
            }
            $this->data[$staff->getId()] = $data;
        }

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $this->data = $modifier->modifyData($this->data);
        }

        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }
}
