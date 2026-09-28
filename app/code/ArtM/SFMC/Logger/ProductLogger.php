<?php
namespace ArtM\SFMC\Logger;
 
class ProductLogger extends \Monolog\Logger
{

    public function productInfo($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }
}
