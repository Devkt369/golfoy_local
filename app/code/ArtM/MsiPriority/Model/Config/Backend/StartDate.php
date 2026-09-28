<?php

namespace ArtM\MsiPriority\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class StartDate extends Value
{
    /**
     * Validate start date field
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        
        if ($value) {
            // Simple date validation
            $date = \DateTime::createFromFormat('Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                throw new LocalizedException(__('Please enter a valid date in Y-m-d format.'));
            }
        }
        
        return parent::beforeSave();
    }
}
