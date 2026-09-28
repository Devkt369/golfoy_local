<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types = 1);

namespace Magestore\Webpos\Model\DataProvider\Session;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magestore\Webpos\Api\Data\Staff\SessionInterface;
use Magestore\Webpos\Api\DataProvider\Session\GetSessionByIdInterface;
use Magestore\Webpos\Api\Staff\SessionRepositoryInterface;

/**
 * Class GetSessionById
 *
 * Data Provider to get session by id
 */
class GetSessionById extends DataObject implements GetSessionByIdInterface
{
    /**
     * @var SessionRepositoryInterface
     */
    protected $sessionRepository;

    /**
     * GetSessionById constructor.
     *
     * @param SessionRepositoryInterface $sessionRepository
     * @param array $data
     */
    public function __construct(
        SessionRepositoryInterface $sessionRepository,
        $data = []
    ) {
        $this->sessionRepository = $sessionRepository;
        parent::__construct($data);
    }

    /**
     * Execute get by session id
     *
     * @param string|null $sessionId
     * @return SessionInterface|null
     * @throws LocalizedException
     */
    public function execute($sessionId)
    {
        $sessionData = $this->_getData(self::SESSION_DATA);
        if (!is_array($sessionData)) {
            $sessionData = [];
        }
        if (!isset($sessionData[$sessionId])) {
            $sessionData[$sessionId] = $this->sessionRepository->getBySessionId($sessionId);
            $this->setData(self::SESSION_DATA, $sessionData);
        }

        return $sessionData[$sessionId];
    }
}
