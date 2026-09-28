<?php
namespace ArtM\SFMC\Logger;
 
class OrderLogger extends \Monolog\Logger
{

    public function orderInfo($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }
}
