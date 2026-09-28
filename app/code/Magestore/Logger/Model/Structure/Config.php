<?php
/**
 * High-level interface for catalog attributes data that hides format from the client code
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Logger\Model\Structure;

use Magestore\Logger\Model\Structure\Config\Data;

/**
 * Class Config
 *
 * Read config interface
 */
class Config
{
    /**
     * @var Data
     */
    protected $dataStorage;

    /**
     * @param Data $dataStorage
     */
    public function __construct(Data $dataStorage)
    {
        $this->dataStorage = $dataStorage;
    }

    /**
     * Retrieve attributes of log type
     *
     * @param string $type Name of an attribute group
     * @return array
     */
    public function getFields(string $type)
    {
        return $this->dataStorage->get($type, []);
    }

    /**
     * Retrieve all log type
     *
     * @return array
     */
    public function getAllType()
    {
        return $this->dataStorage->get();
    }
}
