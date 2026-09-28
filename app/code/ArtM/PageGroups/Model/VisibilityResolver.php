<?php

namespace ArtM\PageGroups\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class VisibilityResolver
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(TimezoneInterface $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * @param array|object $image
     * @return bool
     */
    public function isVisible($image)
    {
        $isHidden = (int)$this->getValue($image, 'is_hidden', 0);
        if ($isHidden === 1) {
            return false;
        }

        $mode = strtolower((string)$this->getValue($image, 'visibility_mode', 'keep'));
        if (!in_array($mode, ['keep', 'show', 'hide'], true)) {
            $mode = 'keep';
        }

        if ($mode === 'keep') {
            return true;
        }

        $nowTimestamp = (int)strtotime((string)$this->timezone->date()->format('Y-m-d H:i:s'));
        $startAt = $this->normalizeDate($this->getValue($image, 'start_at'));
        $endAt = $this->normalizeDate($this->getValue($image, 'end_at'));

        $startOk = $startAt === null || $nowTimestamp >= (int)strtotime($startAt);
        $endOk = $endAt === null || $nowTimestamp <= (int)strtotime($endAt);
        $inRange = $startOk && $endOk;

        if ($mode === 'show') {
            return $inRange;
        }

        // mode === hide
        return !$inRange;
    }

    /**
     * @param array|object $source
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getValue($source, $key, $default = null)
    {
        if (is_array($source)) {
            return array_key_exists($key, $source) ? $source[$key] : $default;
        }

        if (is_object($source)) {
            if (method_exists($source, 'getData')) {
                $value = $source->getData($key);
                return $value !== null ? $value : $default;
            }
            if (isset($source->{$key})) {
                return $source->{$key};
            }
        }

        return $default;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function normalizeDate($value)
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }
}
