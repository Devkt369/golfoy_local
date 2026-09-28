<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposMobile\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magestore\PaymentOffline\Service\PaymentOfflineService;

/**
 * Schema patch to add pay later
 */
class CreatePayLaterPatch implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PaymentOfflineService
     */
    protected $paymentOfflineService;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param LoggerInterface $logger
     * @param PaymentOfflineService $paymentOfflineService
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger,
        PaymentOfflineService $paymentOfflineService
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
        $this->paymentOfflineService = $paymentOfflineService;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        try {
            $paymentData = [
                'payment_code' => 'wpo_paylater',
                'enable' => 0,
                'title' => 'Pay Later',
                'use_reference_number' => 0,
                'icon_type' => 1,
                'icon_default' => 'default/COD.svg',
                'use_pay_later' => 1,
                'sort_order' => 0,
                'icon_path' => 'default/COD.svg'
            ];
            $this->paymentOfflineService->removePayment($paymentData['payment_code']);
            $this->paymentOfflineService->createPaymentOffline($paymentData);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}
