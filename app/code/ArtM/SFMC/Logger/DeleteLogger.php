<?php
namespace ArtM\SFMC\Logger;
 
class DeleteLogger extends \Monolog\Logger
{

    public function productInfo($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }
}
