<?php 

namespace ArtM\SFMC\Block\Adminhtml\Cron;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use ArtM\SFMC\Helper\Data;

class BearerKeyLastUpdate extends Field
{
    /**
     * Helper\Data
     *
     * @var [$data]
     */
    protected $data;

    /**
     * Render element value
     *
     * @param AbstractElement $element
     * @return string
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Data $helperData,
        array $data = []
    ){
        parent::__construct($context , $data);
        $this->_helperData = $helperData;
    }
    public function _getElementHtml(AbstractElement $element)
    {
        $value = $this->_helperData->lastUpdateBearerKey();
        If($value != null){
            return $value;
        }else{
            return 'Not found';
        }
        return 'not found';
    }

}
