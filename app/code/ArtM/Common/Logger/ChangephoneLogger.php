<?php

    namespace ArtM\Common\Logger;

    use Monolog\Logger;
    use Magento\Framework\Logger\Handler\Base as BaseHandler;

    class ChangephoneLogger extends Logger
    {
        public function __construct(BaseHandler $handler)
        {
            parent::__construct('custom');
            $this->pushHandler($handler);
        }
    }
