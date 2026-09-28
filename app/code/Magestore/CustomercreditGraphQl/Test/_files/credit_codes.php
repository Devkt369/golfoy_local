<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magestore\Customercredit\Model\Creditcode;
use Magestore\Customercredit\Model\CreditcodeFactory;
use Magestore\Customercredit\Model\ResourceModel\Creditcode as CreditCodeResource;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;

/** @var SourceInterfaceFactory $creditCodeFactory */
$creditCodeFactory = Bootstrap::getObjectManager()->get(CreditcodeFactory::class);
/** @var CreditCodeResource $creditCodeResource */
$creditCodeResource = Bootstrap::getObjectManager()->get(CreditCodeResource::class);

$sampleData = [
    [
        'credit_code' => 'ABCDEF-VALID',
        'currency' => 'USD',
        'description' => 'send code to friend',
        'transaction_time' => '2021-06-09 03:13:57',
        'status' => 1,
        'amount_credit' => 11,
        'recipient_email' => 'test@localhost.com',
        'customer_id' => 0
    ],
    [
        'credit_code' => 'ABCDEF-CANCEL',
        'currency' => 'USD',
        'description' => 'send code to friend',
        'transaction_time' => '2021-06-09 03:13:57',
        'status' => 3,
        'amount_credit' => 11,
        'recipient_email' => 'test@localhost.com',
        'customer_id' => 0
    ]
];

foreach ($sampleData as $data) {
    /** @var Creditcode $creditCode */
    $creditCode = $creditCodeFactory->create();
    $creditCode->setData($data);
    $creditCodeResource->save($creditCode);
}
