<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 *
 */

namespace Magestore\Webpos\Api\Checkout;

/**
 * Interface \Magestore\Webpos\Api\Checkout\PosOrderRepositoryInterface
 */
interface PosOrderRepositoryInterface
{
    /**
     * Process convert order
     *
     * @param string $incrementId
     * @return \Magestore\Webpos\Api\Data\Checkout\OrderInterface|bool
     * @throws \Exception
     */
    public function processConvertOrder($incrementId);

    /**
     * Save pos order model
     *
     * @param \Magestore\Webpos\Api\Data\Checkout\PosOrderInterface $posOrder
     * @return \Magestore\Webpos\Api\Data\Checkout\PosOrderInterface
     */
    public function save(\Magestore\Webpos\Api\Data\Checkout\PosOrderInterface $posOrder);

    /**
     * Get pos order by increment id
     *
     * @param string $id
     * @return \Magestore\Webpos\Api\Data\Checkout\PosOrderInterface
     */
    public function get(string $id);
}
