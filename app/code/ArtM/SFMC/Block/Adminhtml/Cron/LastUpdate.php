<?php

namespace ArtM\SFMC\Block\Adminhtml\Cron;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use ArtM\SFMC\Model\SfmcCustomerBatchFactory;

class LastUpdate extends Field
{
    /**
     * @var SfmcCustomerBatchFactory
     */
    private $SfmcCustomerBatchFactory;

    /**
     * LastUpdate constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \ArtM\SFMC\Model\SfmcCustomerBatchFactory $SfmcCustomerBatchFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        SfmcCustomerBatchFactory $SfmcCustomerBatchFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->SfmcCustomerBatchFactory = $SfmcCustomerBatchFactory;
    }

    /**
     * Render element value
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        // Fetch the last_updated value from the database
        $lastUpdated = $this->getLastUpdated();

        return $lastUpdated;
    }

    /**
     * Get last_updated value from the database
     *
     * @return string|null
     */
    public function getLastUpdated()
    {
        try {
            $SfmcCustomerBatchModel = $this->SfmcCustomerBatchFactory->create();
            $collection = $SfmcCustomerBatchModel->getCollection()->setOrder('last_updated', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->setPageSize(1);
            $lastUpdated = $collection->getFirstItem()->getData('last_updated');
            if( $lastUpdated != null){
                return $lastUpdated;
            }else{
                return 'Date & Time not found';
            }            
        } catch (\Exception $e) {
            return 'Date & Time not found';
        }
        return 'Date & Time not found';
    }
}
