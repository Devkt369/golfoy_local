<?php
namespace ArtM\Common\Logger;

use Magento\Framework\Logger\Handler\Base as BaseHandler;

class Handler extends BaseHandler
{
    protected $fileName = '/var/log/ChangephoneLogger.log';
}