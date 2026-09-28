<?php

namespace Magestore\ClickAndCollectAdminUi\Block\Adminhtml\Location\Edit\Tab\StoreSchedule;

use Magento\Backend\Block\Template;
use Magestore\ClickAndCollect\Helper\Data;

/**
 * Model Note
 */
class Note extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Magestore_ClickAndCollectAdminUi::location/note.phtml';

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * Note constructor.
     * @param Template\Context $context
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
    }

    /**
     * Retrieve stores collection with default store
     *
     * @return string
     */
    public function getNoteForScheduleLocation()
    {
        return $this->dataHelper->getBackendUrl('clickandcollectadmin/schedule');
    }
}
