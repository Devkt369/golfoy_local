<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\WebposStripe\Controller\Adminhtml\Api;

use Exception;
use Magento\Framework\Controller\Result\Json;
use Magestore\WebposStripe\Controller\Adminhtml\AbstractAction;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\AuthenticationException;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

/**
 * Class Test
 *
 * Use for test api stripe
 */
class Test extends AbstractAction implements HttpGetActionInterface
{
    /**
     * @return Json $resultJson
     */
    public function execute()
    {
        $response = [
            'url' => '',
            'message' => '',
            'success' => true
        ];
        $isEnable = $this->stripeService->isEnable();
        if ($isEnable) {
            try {
                $this->stripeService->testConnectToApi();
            } catch (AuthenticationException $exception) {  // phpstan:ignore
                $response['success'] = false;
                $response['message'] =  __(
                    'Connection failed. Please contact admin to check the configuration of API.'
                );
            } catch (ApiConnectionException $exception) { // phpstan:ignore
                $response['success'] = false;
                $response['message'] =  __(
                    'Please check your network connection'
                );
            } catch (Exception $exception) {
                return $this->createJsonResult($response);
            }
        } else {
            $message = $this->stripeService->getConfigurationError();
            $response['success'] = false;
            $response['message'] = __($message);
        }
        return $this->createJsonResult($response);
    }
}
