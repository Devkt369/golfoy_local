<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model;

use Magestore\Logger\Model\Structure\Config as FormatReader;
use Magestore\LoggerApi\Api\ReformatFieldByStructureOrderInterface;

/**
 * Service for validate log format
 *
 * @api
 */
class ReformatFieldByStructureOrder implements ReformatFieldByStructureOrderInterface
{
    /**
     * @var FormatReader
     */
    protected $formatReader;

    /**
     * ReformatFieldByStructureOrder constructor.
     *
     * @param FormatReader $formatReader
     */
    public function __construct(
        FormatReader $formatReader
    ) {
        $this->formatReader = $formatReader;
    }

    /**
     * Execute service
     *
     * @param array $message
     * @param string $type
     * @return array
     */
    public function execute(array $message, string $type): array
    {
        $formatType = $this->formatReader->getFields($type);
        usort($formatType, function ($a, $b) {
            return (int) $a['sortOrder'] - (int) $b['sortOrder'];
        });
        $fieldOrder = array_column($formatType, 'name');
        uksort($message, function ($a, $b) use ($fieldOrder) {
            foreach ($fieldOrder as $value) {
                if ($a == $value) {
                    return 0;
                }
                if ($b == $value) {
                    return 1;
                }
            }
            return 1;
        });
        return $message;
    }
}
