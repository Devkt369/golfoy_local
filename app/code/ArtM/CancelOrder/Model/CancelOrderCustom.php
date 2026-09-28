<?php 

namespace ArtM\CancelOrder\Model;


//use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
//use Magento\Framework\DataObject\IdentityInterface;
//use ArtM\CancelOrder\Api\Data\CancelOrderCustomInterface;


class CancelOrderCustom extends \Magento\Framework\Model\AbstractModel
{
   // const CACHE_TAG = 'cancel_order_custom';

    public function _construct()
    {
        $this->_init('ArtM\CancelOrder\Model\ResourceModel\CancelOrderCustom');
    }


    // public function getState()
    // {
    //     return $this->getData(self::ORDERSTATE);
    // }

    //  public function getStatus()
    // {
    //     return $this->getData(self::ORDERSTATUS);
    // }

    // public function getCreatedAt()
    // {
    //     return $this->getData(self::CREATED_AT);
    // }

    // public function getId()
    // {
    //     return $this->getData(self::ID);
    // }
    //   public function getOrderid()
    // {
    //     return $this->getData(self::ORDERID);
    // }

    //    public function getIdentities()
    // {
    //     return [self::CACHE_TAG . '_' . $this->getId()];
    // }

    // public function setOrderid($orderid)
    // {
    //     return $this->setData(self::ORDERID, $orderid);
    // }

    //  public function setState($state)
    // {
    //     return $this->setData(self::ORDERSTATE, $state);
    // }


    //  public function setStatus($status)
    // {
    //     return $this->setData(self::ORDERSTATUS, $status);
    // }

    //   public function setCreatedAt($createdAt)
    // {
    //     return $this->setData(self::CREATED_AT, $createdAt);
    // }

    // public function setId($id)
    // {
    //     return $this->setData(self::POST_ID, $id);
    // }


}