<?php
namespace ArtM\CouponValidation\Model;

use Magento\Framework\File\Csv as MagentoCsv;
use Psr\Log\LoggerInterface;

/**
 * CSV Processor for Coupon Validation
 */
class CsvProcessor
{
    /** @var MagentoCsv */
    private $csv;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        MagentoCsv $csv,
        LoggerInterface $logger
    ) {
        $this->csv = $csv;
        $this->logger = $logger;
    }

    /**
     * Parse CSV into array of associative rows using header row as keys
     *
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public function parse(string $filePath): array
    {
        if (!is_readable($filePath)) {
            throw new \Exception('CSV file is not readable');
        }

        $rows = $this->csv->getData($filePath);
        if (empty($rows)) {
            return [];
        }

        $header = array_shift($rows);
        $header = array_map(function ($h) {
            return strtolower(trim($h));
        }, $header);

        $result = [];
        foreach ($rows as $r) {
            $assoc = [];
            foreach ($header as $i => $col) {
                $assoc[$col] = isset($r[$i]) ? $r[$i] : null;
            }
            $result[] = $assoc;
        }

        return $result;
    }

    /**
     * Return rows that have need_coupon = yes (case-insensitive)
     * If header missing, returns empty array
     *
     * @param string $filePath
     * @return array
     */
    public function getRowsNeedingCoupon(string $filePath): array
    {
        $rows = $this->parse($filePath);
        if (empty($rows)) {
            $this->logger->debug('CsvProcessor: No rows parsed from CSV');
            return [];
        }

        // Normalize header keys (remove non-alphanum) for heuristic matching
        $firstRow = reset($rows);
        $headers = array_keys($firstRow);
        $this->logger->debug('CsvProcessor: Headers found', ['headers' => $headers]);
        
        $normMap = [];
        foreach ($headers as $h) {
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower($h));
            $normMap[$h] = $norm;
        }
        $this->logger->debug('CsvProcessor: Normalized headers', ['normMap' => $normMap]);

        // Values that mean "needs coupon" for need_coupon column
        $positiveValues = ['yes', 'y', '1', 'true', 't'];
        // Values that mean "doesn't need coupon" for purchased columns
        $negativeValues = ['false', '0', 'no', 'n', 'f', ''];

        $filtered = [];

        foreach ($rows as $rowIndex => $row) {
            $matched = false;

            // Prefer explicit need_coupon header
            foreach ($normMap as $orig => $norm) {
                if ($norm === 'needcoupon') {
                    $val = isset($row[$orig]) ? strtolower(trim((string)$row[$orig])) : '';
                    $this->logger->debug("CsvProcessor: Row $rowIndex - need_coupon value", [
                        'raw_value' => $row[$orig] ?? 'NOT SET',
                        'normalized_value' => $val,
                        'is_positive' => in_array($val, $positiveValues, true)
                    ]);
                    // For need_coupon: TRUE/true/1/yes = needs coupon (include)
                    // FALSE/false/0/no = doesn't need coupon (exclude)
                    if (in_array($val, $positiveValues, true)) {
                        $filtered[] = $row;
                    }
                    $matched = true;
                    break;
                }
            }
            if ($matched) {
                continue;
            }

            // If we have a purchased* style header, treat FALSE/0/NO as "needs coupon"
            foreach ($normMap as $orig => $norm) {
                if (strpos($norm, 'purchased') !== false) {
                    $val = isset($row[$orig]) ? strtolower(trim((string)$row[$orig])) : '';
                    if (in_array($val, $negativeValues, true)) {
                        $filtered[] = $row;
                    }
                    $matched = true;
                    break;
                }
            }

            // No recognized header — no-op (row not selected)
        }

        $this->logger->debug('CsvProcessor: Filtering complete', [
            'total_rows' => count($rows),
            'filtered_rows' => count($filtered)
        ]);

        return $filtered;
    }
}
