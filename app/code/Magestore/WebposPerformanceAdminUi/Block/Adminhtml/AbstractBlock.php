<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\WebposPerformanceAdminUi\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\ResolverInterface as TimezoneResolver;

/**
 * Class AbstractBlock
 *
 * Used for abstract block
 */
abstract class AbstractBlock extends Template
{
    /* @var TimezoneResolver */
    protected $timezoneResolver;

    /**
     * Internal constructor, that is called from real constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $objectManager = ObjectManager::getInstance();
        $this->timezoneResolver = $objectManager->get(TimezoneResolver::class);
    }

    /**
     * Format date
     *
     * @param string $date
     * @return string
     * @throws \Exception
     */
    public function formatDateDisplay(string $date)
    {
        try {
            $dateTime = new \DateTime($date);
            $formatter = new \IntlDateFormatter(
                $this->timezoneResolver->getLocale(),
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::NONE
            );
            return $formatter->format($dateTime);
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * Get all performance log url
     *
     * @return string
     */
    public function getAllActionResponseTable()
    {
        return $this->getUrl('webposperformance/index/overview');
    }
}
