<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Core\Model\Connector\Http;

/**
 * Represents converter interface for http request and response body.
 *
 * @api
 * @since 100.2.0
 */
interface ConverterInterface
{
    /**
     * From Body
     *
     * @param string $body
     *
     * @return array
     * @since 100.2.0
     */
    public function fromBody($body);

    /**
     * To Body
     *
     * @param array $data
     *
     * @return string
     * @since 100.2.0
     */
    public function toBody(array $data);

    /**
     * Get Content Type Header
     *
     * @return string
     * @since 100.2.0
     */
    public function getContentTypeHeader();

    /**
     * Get Content Media Type
     *
     * @return string
     * @since 100.3.0
     */
    public function getContentMediaType(): string;
}
