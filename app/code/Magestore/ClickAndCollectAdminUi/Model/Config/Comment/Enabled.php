<?php
/**
 * Magestore.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_ClickAndCollectAdminUi
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
namespace Magestore\ClickAndCollectAdminUi\Model\Config\Comment;

/**
 * Model Enabled
 * @module   ClickAndCollectAdminUi
 * @author   Magestore Developer
 */
class Enabled extends AbstractComment
{
    /**
     * Retrieve element comment by element value.
     *
     * @param string $elementValue
     *
     * @return string
     */
    public function getCommentText($elementValue)
    {
        return __(
            'If you enable this method, the In-store Delivery method of Magento will be automatically disabled.
            On the other hand, when you enable In-store Delivery method of Magento, 
            this method will be automatically disabled too.To use the Click &amp; Collect function,
            you must fill the Google API Key on <a href="%1">POS Settings</a>--> General Configuration.',
            $this->_url->getUrl('adminhtml/system_config/edit/section/webpos')
        );
    }
}
