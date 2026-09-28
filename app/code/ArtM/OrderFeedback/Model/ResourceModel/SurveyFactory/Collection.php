<?php 
namespace ArtM\OrderFeedback\Model\ResourceModel\SurveyFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \ArtM\OrderFeedback\Model\SurveyFactory::class,
            \ArtM\OrderFeedback\Model\ResourceModel\SurveyFactory::class
	    );
    }
}