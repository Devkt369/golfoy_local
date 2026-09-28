<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\QuoteRepository\Plugin;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magestore\Webpos\Api\DataProvider\GetCurrentStaffIdBySessionInterface;

/**
 * The plugin checks if the user has ability to change the quote.
 *
 * Class AccessChangeQuoteControl
 */
class AccessChangeQuoteControl
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $apiRequest;

    /**
     * @var GetCurrentStaffIdBySessionInterface
     */
    protected $getCurrentStaffIdBySession;

    /**
     * AccessChangeQuoteControl constructor.
     *
     * @param UserContextInterface $userContext
     * @param RestRequest $apiRequest
     * @param GetCurrentStaffIdBySessionInterface $getCurrentStaffIdBySession
     */
    public function __construct(
        UserContextInterface $userContext,
        RestRequest $apiRequest,
        GetCurrentStaffIdBySessionInterface $getCurrentStaffIdBySession
    ) {
        $this->userContext = $userContext;
        $this->apiRequest = $apiRequest;
        $this->getCurrentStaffIdBySession = $getCurrentStaffIdBySession;
    }

    /**
     * Rewrite function
     *
     * @param CartRepositoryInterface $subject
     * @param CartInterface $quote
     * @return array
     * @throws StateException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(CartRepositoryInterface $subject, CartInterface $quote)
    {
        $sessionId = $this->apiRequest->getParam(
            \Magestore\WebposIntegration\Controller\Rest\RequestProcessor::SESSION_PARAM_KEY
        );
        if ($sessionId) {
            $currentStaff = $this->getCurrentStaffIdBySession->execute($sessionId);
            if ($currentStaff) {
                return [$quote];
            }
        }

        if (!$this->isAllowed($quote)) {
            throw new StateException(__("Invalid state change requested"));
        }
        return [$quote];
    }

    /**
     * Checks if user is allowed to change the quote.
     *
     * @param Quote $quote
     * @return bool
     */
    private function isAllowed(Quote $quote)
    {
        switch ($this->userContext->getUserType()) {
            case UserContextInterface::USER_TYPE_CUSTOMER:
                $isAllowed = ($quote->getCustomerId() == $this->userContext->getUserId());
                break;
            case UserContextInterface::USER_TYPE_GUEST:
                $isAllowed = ($quote->getCustomerId() === null);
                break;
            case UserContextInterface::USER_TYPE_ADMIN:
            case UserContextInterface::USER_TYPE_INTEGRATION:
                $isAllowed = true;
                break;
            default:
                $isAllowed = false;
        }

        return $isAllowed;
    }
}
