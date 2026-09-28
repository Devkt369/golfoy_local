<?php

namespace ArtM\CancelOrder\Api\Data;

interface CancelOrderCustomInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ID               = 'id';
    const ORDERID                 = 'order_id';
    const ORDERSTATE               = 'order_state';
    const ORDERSTATUS               = 'order_state';
    const CREATED_AT            = 'created_at';
    /**#@-*/


 public function getOrderid();
   
    public function getState();

    public function getStatus();

   
    public function getCreatedAt();

 
    public function getId();

    public function setState($state);

 
    public function setStatus($status);

    public function setCreatedAt($createdAt);

 
    public function setId($id);

    public function setOrderid($orderid);
}