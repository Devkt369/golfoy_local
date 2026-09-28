<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Request\Actions;

use Magestore\Webpos\Model\Request\ActionLogFactory;
use Magestore\WebposIntegration\Controller\Rest\RequestProcessor;
use Magestore\Webpos\Api\DataProvider\Session\GetSessionByIdInterface;

/**
 * Class AbstractAction
 *
 * Abstract action for the request
 */
class AbstractAction
{
    /**
     * @var string
     */
    const ACTION_TYPE = "";
    /**
     * @var ActionLogFactory
     */
    protected $actionLogFactory;
    /**
     * @var \Magestore\Webpos\Log\Logger
     */
    protected $logger;
    /**
     * @var GetSessionByIdInterface
     */
    protected $getSessionById;

    /**
     * AbstractAction constructor.
     *
     * @param ActionLogFactory $actionLogFactory
     * @param \Magestore\Webpos\Log\Logger $logger
     * @param GetSessionByIdInterface $getSessionById
     */
    public function __construct(
        ActionLogFactory $actionLogFactory,
        \Magestore\Webpos\Log\Logger $logger,
        GetSessionByIdInterface $getSessionById
    ) {
        $this->actionLogFactory = $actionLogFactory;
        $this->logger = $logger;
        $this->getSessionById = $getSessionById;
    }

    /**
     * Save request place order
     *
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveRequest(\Magento\Framework\Webapi\Rest\Request $request)
    {
        $params = $request->getBodyParams();

        $sessionId = $request->getParam(RequestProcessor::SESSION_PARAM_KEY);
        $session = $this->getSessionById->execute($sessionId);

        $data = [
            'action_type' => static::ACTION_TYPE,
            'status' => \Magestore\Webpos\Model\Request\ActionLog::STATUS_PENDING,
            'location_id' => $session->getLocationId(),
            'pos_staff_id' => $session->getStaffId(),
            'pos_id' => $session->getPosId(),
            'params' => json_encode($params),
        ];

        $data = $this->prepareParams($data, $params);

        // Check request data before save
        $isValidate = $this->validate($data);

        if (!$isValidate) {
            return;
        }

        try {
            /** @var \Magestore\Webpos\Model\Request\ActionLog $actionLog */
            $actionLog = $this->actionLogFactory->create();
            $actionLog->setData($data)->save();
        } catch (\Exception $e) {
            $this->logger->info($e->getTraceAsString());
        }
    }

    /**
     * Validate data before save
     *
     * @param array $data
     * @return bool
     */
    public function validate($data)
    {
        $actionLog = $this->actionLogFactory->create();
        $actionLog->load($data['request_increment_id'], 'request_increment_id');

        if (!$actionLog->getId()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Prepare params
     *
     * @param array $data
     * @param array $params
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function prepareParams($data, $params)
    {
        return $data;
    }
}
