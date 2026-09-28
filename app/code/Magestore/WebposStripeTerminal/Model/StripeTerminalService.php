<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposStripeTerminal\Model;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\ObjectManagerInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\Webpos\Model\Checkout\PosOrder;
use Magestore\WebposStripeTerminal\Api\Data\CapturePaymentIntentRequestInterface;
use Magestore\WebposStripeTerminal\Api\Data\CapturePaymentIntentResponseInterface;
use Magestore\WebposStripeTerminal\Api\Data\ConnectedReaderInterface;
use Magestore\WebposStripeTerminal\Api\Data\ConnectionTokenResponseInterface;
use Magestore\WebposStripeTerminal\Api\Data\CreatePaymentIntentRequestInterface;
use Magestore\WebposStripeTerminal\Api\Data\CreatePaymentIntentResponseInterface;
use Magestore\WebposStripeTerminal\Api\Data\RefundPaymentIntentResponseInterface;
use Magestore\WebposStripeTerminal\Api\Data\RegisterReaderRequestInterface;
use Magestore\WebposStripeTerminal\Api\Data\RegisterReaderResponseInterface;
use Magestore\WebposStripeTerminal\Api\Data\SaveConnectedReaderRequestInterface;
use Magestore\WebposStripeTerminal\Api\Data\SaveConnectedReaderResponseInterface;
use Magestore\WebposStripeTerminal\Api\StripeTerminalServiceInterface;
use Magestore\WebposStripeTerminal\Helper\Data;
use Magestore\WebposStripeTerminal\Model\ConnectedReader\ConnectedReaderFactory;
use Magestore\WebposStripeTerminal\Model\Data\SaveConnectedReaderRequest;
use Magestore\WebposStripeTerminal\Api\ConnectedReaderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Stripe\Charge;
use Stripe\Collection;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\StripeObject;
use Stripe\Terminal\ConnectionToken;
use Stripe\Terminal\Location;
use Stripe\Terminal\Reader;

/**
 * Class StripeTerminalService
 *
 * Used to connect to stripe terminal
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StripeTerminalService implements StripeTerminalServiceInterface
{

    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var ConnectedReaderFactory
     */
    protected $factory;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * StripeTerminalService constructor.
     *
     * @param Data $helper
     * @param ConnectedReaderFactory $factory
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        Data $helper,
        ConnectedReaderFactory $factory,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->factory = $factory;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Connection token
     *
     * @return ConnectionTokenResponseInterface
     * @throws StateException
     */
    public function connectionToken()
    {
        try {
            // phpstan:ignore
            Stripe::setApiKey($this->helper->getSecretKey());
            // phpstan:ignore
            $response = ConnectionToken::create();
            /**
             * @var ConnectionTokenResponseInterface $responseData
             */
            $responseData = ObjectManager::getInstance()->create(
                ConnectionTokenResponseInterface::class
            );

            // phpstan:ignore
            if ($response instanceof StripeObject) {
                $responseData->setData($response->toArray());

                return $responseData;
            }

            return $responseData;
        } catch (Exception $e) {
            throw new StateException(
                __($e->getMessage())
            );
        }
    }

    /**
     * API create payment intent which auth card, no charge
     *
     * @param CreatePaymentIntentRequestInterface $request
     * @return CreatePaymentIntentResponseInterface|null
     * @throws StateException
     */
    public function createPaymentIntent($request)
    {
        try {
            // phpstan:ignore
            Stripe::setApiKey($this->helper->getSecretKey());

            $data = $request->getData();

            // Support interac card for canada market
            if ($this->helper->isAllowInteracCard()) {
                $data['payment_method_types'] = ['card_present', 'interac_present'];
            } else {
                $data['payment_method_types'] = ['card_present'];
            }
            $data['capture_method'] = 'manual';

            // phpstan:ignore
            $response = PaymentIntent::create($data);

            if (empty($response)) {
                throw new StateException(
                    __('Connection failed. Please contact admin to check the configuration of API.')
                );
            }

            /**
             * @var CreatePaymentIntentResponseInterface $responseData
             */
            $responseData = ObjectManager::getInstance()->create(
                CreatePaymentIntentResponseInterface::class
            );

            // phpstan:ignore
            if ($response instanceof StripeObject) {
                $response = $response->toArray();
                $responseData->setIntent($response['id']);
                $responseData->setSecret($response['client_secret']);

                return $responseData;
            }

            return $responseData;
        } catch (Exception $e) {
            throw new StateException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Capture payment
     *
     * @param CapturePaymentIntentRequestInterface $request
     * @return CapturePaymentIntentResponseInterface
     * @throws StateException
     */
    public function capturePaymentIntent($request)
    {
        try {
            // phpstan:ignore
            Stripe::setApiKey($this->helper->getSecretKey());

            /** @var PaymentIntent $paymentIntent */
            // phpstan:ignore
            $paymentIntent = PaymentIntent::retrieve(
                $request->getPaymentIntentId()
            );

            $response = $paymentIntent->capture();

            if (empty($response)) {
                throw new StateException(
                    __('Connection failed. Please contact admin to check the configuration of API.')
                );
            }

            /**
             * @var CapturePaymentIntentResponseInterface $responseData
             */
            $responseData = ObjectManager::getInstance()->create(
                CapturePaymentIntentResponseInterface::class
            );

            // phpstan:ignore
            if ($response instanceof StripeObject) {
                $response = $response->toArray();
                $responseData->setIntent($response['id']);
                $responseData->setSecret($response['client_secret']);

                return $responseData;
            }

            return $responseData;
        } catch (Exception $e) {
            throw new StateException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Register reader
     *
     * @param RegisterReaderRequestInterface $request
     * @return RegisterReaderResponseInterface
     * @throws StateException
     */
    public function registerReader($request)
    {
        try {
            $secret = $this->helper->getSecretKey();
            $stripeLocationId = $this->getStripeLocationId($secret);
            if (!$stripeLocationId) {
                throw new StateException(
                    __('Not Found Location!')
                );
            }
            // phpstan:ignore
            Stripe::setApiKey($secret);
            $data = $request->getData();
            $data['location'] = $stripeLocationId;
            // phpstan:ignore
            $response = Reader::create($data);
            /**
             * @var RegisterReaderResponseInterface $responseData
             */
            $responseData = ObjectManager::getInstance()->create(
                RegisterReaderResponseInterface::class
            );

            // phpstan:ignore
            if ($response instanceof StripeObject) {
                $responseData->setData($response->toArray());

                return $responseData;
            }

            return $responseData;
        } catch (Exception $e) {
            throw new StateException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Refund payment
     *
     * @param RegisterReaderRequestInterface $request
     * @return RefundPaymentIntentResponseInterface
     * @throws StateException
     */
    public function refundPaymentIntent($request)
    {
        try {
            // phpstan:ignore
            Stripe::setApiKey($this->helper->getSecretKey());
            /** @var PaymentIntent $paymentIntent */
            // phpstan:ignore
            $paymentIntent = PaymentIntent::retrieve(
                $request->getPaymentIntentId()
            );

            /** @var Collection $charges */
            $charges = $paymentIntent->charges;

            if (!$charges->count()) {
                throw new StateException(
                    __('Cannot found charge.')
                );
            }

            /** @var Charge $charge */
            $charge = array_values($charges->data)[0];

            $data = $request->getData();
            unset($data['payment_intent_id']);
            $data['charge'] = $charge->id;

            // phpstan:ignore
            $response = Refund::create($data);
            /**
             * @var RefundPaymentIntentResponseInterface $responseData
             */
            $responseData = ObjectManager::getInstance()->create(
                RefundPaymentIntentResponseInterface::class
            );

            // phpstan:ignore
            if ($response instanceof StripeObject) {
                $responseData->setData($response->toArray());

                return $responseData;
            }

            return $responseData;
        } catch (Exception $e) {
            throw new StateException(
                __($e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getChargeId($request)
    {
        try {
            // phpstan:ignore
            Stripe::setApiKey($this->helper->getSecretKey());
            /** @var PaymentIntent $paymentIntent */
            // phpstan:ignore
            $paymentIntent = PaymentIntent::retrieve(
                $request->getPaymentIntentId()
            );

            /** @var Collection $charges */
            $charges = $paymentIntent->charges;

            if (!$charges->count()) {
                throw new StateException(
                    __('Cannot found charge.')
                );
            }

            /** @var Charge $charge */
            $charge = array_values($charges->data)[0];

            return $charge->id;
        } catch (Exception $e) {
            throw new StateException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Create new connected reader
     *
     * @return ConnectedReader\ConnectedReader
     */
    public function createNewConnectedReader()
    {
        return $this->factory->create();
    }

    /**
     * Save connected reader
     *
     * @param SaveConnectedReaderRequestInterface $request
     * @return SaveConnectedReaderResponseInterface
     * @throws StateException
     */
    public function saveConnectedReader($request)
    {
        try {
            /** @var ObjectManagerInterface $objectManager */
            $objectManager = $this->helper->getObjectManager();
            /** @var ConnectedReaderRepositoryInterface $connectedReaderRepository */
            $connectedReaderRepository = $objectManager->create(
                ConnectedReaderRepositoryInterface::class
            );
            try {
                /** @var ConnectedReaderInterface $connectedReader */
                $connectedReader = $connectedReaderRepository->getPosByPosId($request->getPosId());
            } catch (Exception $e) {
                $connectedReader = $this->createNewConnectedReader();
            }

            if (!$connectedReader->getId()) {
                $connectedReader = $this->createNewConnectedReader();
            }

            /** @var SaveConnectedReaderRequest $request */
            $connectedReader->setPosId($request->getPosId());
            $connectedReader->setReaderId($request->getReaderId());
            $connectedReader->setReaderLabel($request->getReaderLabel());
            $connectedReader->setIpAddress($request->getIpAddress());
            $connectedReader->setSerialNumber($request->getSerialNumber());
            $connectedReaderRepository->save($connectedReader);
            /**
             * @var SaveConnectedReaderResponseInterface $responseData
             */
            $responseData = $objectManager->create(
                SaveConnectedReaderResponseInterface::class
            );
            $responseData->setData($request->getData());

            return $responseData;
        } catch (Exception $e) {
            throw new StateException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Get is enable
     *
     * @return bool
     */
    public function isEnable()
    {
        return $this->helper->validateRequiredSDK() && $this->helper->isEnabled();
    }

    /**
     * Validate env
     *
     * @return bool
     * @throws LocalizedException
     */
    public function validateEnv()
    {
        if (!$this->helper->validateRequiredSDK()) {
            throw new LocalizedException(
                __('Stripe SDK not found, please go to the configuration to get the instruction to install the SDK')
            );
        }

        if (!$this->helper->isEnabled()) {
            throw new LocalizedException(__('Stripe Terminal is disabled'));
        }

        if (empty($this->helper->getSecretKey())) {
            throw new LocalizedException(__('Secret key are required'));
        }

        return true;
    }

    /**
     * Test connect stripe API
     *
     * @return bool
     * @throws Exception
     */
    public function connectToApi()
    {
        // phpstan:ignore
        Stripe::setApiKey($this->helper->getSecretKey());
        try {
            // phpstan:ignore
            $this->testCreate();
            return true;
        } catch (AuthenticationException $exception) { // phpstan:ignore
            throw new LocalizedException(
                __('Connection failed. Please contact admin to check the configuration of API.')
            );
        } catch (ApiConnectionException $exception) { // phpstan:ignore
            throw new LocalizedException(
                __('Please check your network connection.')
            );
        } catch (Exception $exception) {
            /* Exception but key is correct */
            return true;
        }
    }

    /**
     * Test create stripe payment
     *
     * @return Charge
     * @throws ApiErrorException
     */
    public function testCreate()
    {
        $card = [
            'number'    => '4242424242424242',
            'exp_month' => 5,
            'exp_year'  => date('Y') + 1,
        ];

        // phpstan:ignore
        return Charge::create(
            [
                'amount'   => 100,
                'currency' => 'usd',
                'card'     => $card,
            ]
        );
    }

    /**
     * Get stripe Location id
     *
     * @param  string $secret
     * @return bool|string
     * @throws StateException
     * @throws ApiErrorException
     */
    public function getStripeLocationId($secret = null)
    {
        if (!$this->helper->validateRequiredSDK()) {
            return false;
        }

        $currentSession = $this->helper->getCurrentSession();

        if (!$currentSession) {
            $locationId = $this->request->getParam(
                PosOrder::PARAM_ORDER_LOCATION_ID
            );
        } else {
            $locationId = $currentSession->getLocationId();
        }

        if (!$locationId) {
            return false;
        }

        /** @var LocationRepositoryInterface $locationRepository */
        $locationRepository = $this->helper->getObjectManager()->create(LocationRepositoryInterface::class);
        /** @var LocationInterface $location */
        $location = $locationRepository->getById($locationId);

        $secret = $secret ?: $this->helper->getSecretKey();

        if (!$this->helper->isEnabled() || empty($secret)) {
            return false;
        }

        if ($stripeLocation = $this->getStripeLocation($location, $secret)) {
            return $stripeLocation->id;
        }

        if ($stripeLocation = $this->createLocation($location, $secret)) {
            return $stripeLocation->id;
        }

        return false;
    }

    /**
     * Get stripe location
     *
     * @param LocationInterface $location
     * @param string $secret
     * @return Location|bool
     */
    public function getStripeLocation($location, $secret = null)
    {
        try {
            $secret = $secret ?: $this->helper->getSecretKey();
            // phpstan:ignore
            Stripe::setApiKey($secret);
            // phpstan:ignore
            $registeredLocations = Location::all();
            $stripeLocation = false;
            foreach ($registeredLocations as $registeredLocation) {
                if ($registeredLocation->display_name === $location->getName()) {
                    $stripeLocation = $registeredLocation;
                    break;
                }
            }

            return $stripeLocation;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Create location
     *
     * @param LocationInterface $location
     * @param string $secret
     * @return Location|bool
     */
    public function createLocation($location, $secret = null)
    {
        try {
            $secret = $secret ?: $this->helper->getSecretKey();
            // phpstan:ignore
            Stripe::setApiKey($secret);
            // phpstan:ignore
            $response = Location::create(
                [
                    'display_name' => $location->getName(),
                    'address'      => [
                        'line1'       => $location->getStreet(),
                        'city'        => $location->getCity(),
                        'country'     => $location->getCountryId() ? : 'US',
                        'postal_code' => $location->getPostcode(),
                    ],
                ]
            );

            return $response;
        } catch (Exception $e) {
            return false;
        }
    }
}
