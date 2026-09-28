<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model;

use Magento\Framework\Exception\LocalizedException;
use Magestore\LoggerApi\Api\ValidateLogFormatInterface;
use Magestore\Logger\Model\Structure\Config as FormatReader;
use Psr\Log\LoggerInterface;

/**
 * Service for validate log format
 *
 * @api
 */
class ValidateLogFormat implements ValidateLogFormatInterface
{
    /**
     * @var FormatReader
     */
    protected $formatReader;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ValidateLogFormat constructor.
     *
     * @param FormatReader $formatReader
     * @param LoggerInterface $logger
     */
    public function __construct(
        FormatReader $formatReader,
        LoggerInterface $logger
    ) {
        $this->formatReader = $formatReader;
        $this->logger = $logger;
    }

    /**
     * Validate message with type
     *
     * @param array $message
     * @param string $type
     * @return bool
     * @throws LocalizedException
     */
    public function execute(array $message, string $type): bool
    {
        $formatType = $this->formatReader->getFields($type);
        $this->validateRequiredField($message, $formatType);
        $this->validateFieldType($message, $formatType);
        return true;
    }

    /**
     * Validate field key
     *
     * @param array $message
     * @param array $format
     * @return bool
     * @throws LocalizedException
     */
    public function validateRequiredField(array $message, array $format)
    {
        $requiredField = [];
        $fieldInvalidRequired = [];
        foreach ($format as $fieldFormat) {
            if ($fieldFormat['required'] == 'true') {
                $requiredField[] = $fieldFormat['name'];
            }
        }
        $fieldInMessage = array_keys($message);
        $diff = array_diff($requiredField, $fieldInMessage);
        foreach ($diff as $fieldDiff) {
            if (in_array($fieldDiff, $requiredField)) {
                $fieldInvalidRequired[] = $fieldDiff;
            }
        }
        if (count($fieldInvalidRequired)) {
            throw new LocalizedException(__('"%1" is required field.', implode(',', $fieldInvalidRequired)));
        }
        return true;
    }

    /**
     * Validate field type
     *
     * @param array $message
     * @param array $format
     * @return bool
     * @throws LocalizedException
     */
    public function validateFieldType(array $message, array $format)
    {
        $fieldInvalid = [];
        foreach ($message as $field => $value) {
            if (isset($format[$field]['type'])) {
                $formatField = $format[$field]['type'];
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                if (gettype($value) !== $formatField) {
                    $fieldInvalid[] = $field;
                }
            }
        }
        if (count($fieldInvalid)) {
            throw new LocalizedException(__('"%1" is wrong type.', implode(',', $fieldInvalid)));
        }
        return true;
    }
}
