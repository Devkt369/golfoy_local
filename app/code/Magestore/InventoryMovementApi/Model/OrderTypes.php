<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementApi\Model;

/**
 * Class OrderTypes
 *
 * Use for order types
 */
class OrderTypes
{
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
