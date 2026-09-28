<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\Import;

/**
 * Class BranchRequest NoticeMessage
 */
class NoticeMessage extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * NoticeMessage constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context
    ) {
        $this->backendSession = $context->getBackendSession();
        parent::__construct($context);
    }

    /**
     * Is Has Error
     *
     * @return mixed
     */
    public function isHasError()
    {
        return $this->backendSession->getData('error_import', true);
    }

    /**
     * Get Invalid File Csv Url
     *
     * @return string
     */
    public function getInvalidFileCsvUrl()
    {
        return $this->getUrl('branchrequest/inventoryTransfer/downloadinvalidcsv');
    }
}
