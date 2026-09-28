<?php
namespace Snapcommerce\Snapmint\Plugin;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Session\SessionStartChecker;

class CallbackSessionChecker
{
    /**
     * Array
     */
    private const PAYMENT_CALLBACK_PATHS = [
        'snapmint/ipn/callbacksnapmint'
    ];

    /**
     * @var Http
     */
    private $_request;
    private $_logger;

    /**
     * @param Http $request
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Http $request,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_request = $request;
        $this->_logger  = $logger;
    }

    /**
     * Check if session can be started or not, taking payment extension's response action into consideration
     *
     * @param \Magento\Framework\Session\SessionStartChecker $subject
     * @param bool $result
     * @return bool
     */
    public function afterCheck(\Magento\Framework\Session\SessionStartChecker $subject, bool $result) : bool
    {
        if ($result === false) {
            return false;
        }

        $inArray = true;
        foreach (self::PAYMENT_CALLBACK_PATHS as $path) {
            if (strpos((string)$this->_request->getPathInfo(), $path) !== false) {
                $inArray = false;
                break;
            }
        }
        return $inArray;
    }
}
