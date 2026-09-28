<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerialize;
use Magestore\Logger\Model\Structure\Config;
use Magestore\LoggerApi\Api\Data\LogMessageInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Event\ManagerInterface;

/**
 * Submit Log, used for GraphQL submit log from app to Magento.
 */
class SubmitLog implements ResolverInterface
{
    const EVENT_NAME = 'magestore_log';

    const STATUS = 'status';

    /**
     * @var JsonSerialize
     */
    protected $serializer;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * SubmitLog constructor.
     *
     * @param JsonSerialize $serializer
     * @param Logger $logger
     * @param ManagerInterface $eventManager
     * @param Config $config
     */
    public function __construct(
        JsonSerialize $serializer,
        Logger $logger,
        ManagerInterface $eventManager,
        Config $config
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $dataLogs = $args['input'];
        try {
            $logTypeAll = array_keys($this->config->getAllType());
            foreach ($dataLogs as $dataLog) {
                $message = $this->serializer->unserialize($dataLog);
                if (isset($message[LogMessageInterface::LOG_TYPE]) &&
                    in_array($message[LogMessageInterface::LOG_TYPE], $logTypeAll)
                ) {
                    $this->eventManager->dispatch(
                        self::EVENT_NAME,
                        [
                            'format' => $message[LogMessageInterface::LOG_TYPE],
                            'data' => $message
                        ]
                    );
                }
            }
            return [
                self::STATUS => true
            ];
        } catch (\Exception $error) {
             $this->logger->notice($error->getMessage());
            return [
                self::STATUS => false
            ];
        }
    }
}
