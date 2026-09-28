<?php
namespace ArtM\CustomProductDesign\Controller\Userdata;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use ArtM\CustomProductDesign\Model\UserdataFactory;

class Delete extends Action
{
    protected $userdataFactory;

    public function __construct(Context $context, UserdataFactory $userdataFactory)
    {
        parent::__construct($context);
        $this->userdataFactory = $userdataFactory;
    }

    public function execute()
    {
        $result = ['success' => false];
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $userdata = $this->userdataFactory->create()->load($id);
            if ($userdata && $userdata->getId()) {
                $userdata->delete();
                $result['success'] = true;
            }
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
