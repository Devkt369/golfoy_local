<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Payment\Model\Payment;

use Magento\Store\Model\ScopeInterface;

/**
 * Payment - AbstractMethod
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    const ALLOW_SPECIFIC_PAYMENT = 'webpos/payment/allowspecific_payment';
    const SPECIFIC_PAYMENT = 'webpos/payment/specificpayment';

    /**
     * @var string
     */
    protected $enabledPath = '';

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;
    protected $registry;
    protected $extensionFactory;
    protected $customAttributeFactory;
    protected $paymentData;
    protected $scopeConfig;
    protected $logger;
    protected $resource;
    protected $resourceCollection;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\App\Request\Http $request,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->request = $request;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Enable for Web POS only
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return boolean
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isAvailable(?\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $routeName = $this->request->getRouteName();
        $settingEnabled = $this->isEnabledSetting();
        if ($routeName == "webpos" && $settingEnabled == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Is Enabled Setting
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEnabledSetting()
    {
        return (
            $this->_scopeConfig->getValue($this->enabledPath, ScopeInterface::SCOPE_STORE)
            && $this->isAllowOnWebPOS($this->getCode())
        );
    }

    /**
     * Is Allow On Web POS
     *
     * @param string $code
     * @return boolean
     */
    public function isAllowOnWebPOS($code)
    {
        if ($this->_scopeConfig->getValue(self::ALLOW_SPECIFIC_PAYMENT, ScopeInterface::SCOPE_STORE) == '1') {
            $specificpayment = $this->_scopeConfig->getValue(self::SPECIFIC_PAYMENT, ScopeInterface::SCOPE_STORE);
            $specificpayment = explode(',', (string)$specificpayment);
            if (in_array($code, $specificpayment)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }
}
