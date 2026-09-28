<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementApi\Model;

/**
 * Class MetadataObjectTypes
 *
 * Use for generate object type's url
 */
class MetadataObjectTypes
{
    /**
     * Constants for keys of metadata object type
     */
    const VIEW_URL_PATH = 'viewUrlPath';
    const URL_ENTITY_PARAM_NAME = 'urlEntityParamName';
    /**#@-*/

    /**
     * @var array
     */
    protected $data;

    /**
     * ActionTypes constructor.
     * @param array $data
     */
    public function __construct(
        array $data = []
    ) {
        $this->data = $data;
    }

    /**
     * Get Data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
