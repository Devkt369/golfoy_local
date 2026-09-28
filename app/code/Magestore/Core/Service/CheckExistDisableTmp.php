<?php
/**
 * Magestore
 * NOTICE OF LICENSE
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Customercredit
 * @copyright   Copyright (c) 2017 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Core\Service;

/**
 * Class CheckExistDisableTmp
 *
 * Check exist of disableTmp in magento bellow 2.3.2
 */
class CheckExistDisableTmp
{
    /**
     * Check Class Sanitizer exist method extractConfig
     *
     * @return boolean
     */
    public function isExistDisableTmpl()
    {
        // @codingStandardsIgnoreStart
        $className = 'Magento\Framework\View\Element\UiComponent\DataProvider\Sanitizer';
        // @codingStandardsIgnoreEnd
        $methodName = 'extractConfig';
        return class_exists($className) && !!method_exists($className, $methodName);
    }
}
