<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\CustomercreditGraphQl\Test\GraphQl;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test coverage for Redeem Store Credit GraphQl endpoint.
 * Test negative test cases.
 */
class RedeemStoreCreditTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * Setup class
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
    }

    /**
     * @magentoConfigFixture default/customercredit/general/enable 1
     *
     * @magentoDbIsolation disabled
     */
    public function testRedeemStoreCreditIsNotAuthorized()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $mutation
            = <<<MUTATION
mutation {
  msRedeemCreditCode(
    credit_code: "ABCDEF"
  ) {
      status
      message
  }
}
MUTATION;
        $this->graphQlMutation($mutation);
    }

    /**
     * @magentoConfigFixture default/customercredit/general/enable 0
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     *
     * @magentoDbIsolation disabled
     */
    public function testRedeemStoreCreditIsDisabled()
    {
        $userName = 'customer@example.com';
        $password = 'password';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Store Credit is not active');

        $mutation
            = <<<MUTATION
mutation {
  msRedeemCreditCode(
    credit_code: "ABCDEF"
  ) {
      status
      message
  }
}
MUTATION;
        $this->graphQlMutation($mutation, [], '', $this->getCustomerAuthHeaders($userName, $password));
    }

    /**
     * @magentoConfigFixture default/customercredit/general/enable 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     *
     * @magentoDbIsolation disabled
     */
    public function testStoreCreditInvalid()
    {
        $userName = 'customer@example.com';
        $password = 'password';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Code is invalid. Please check again!');

        $mutation
            = <<<MUTATION
mutation {
  msRedeemCreditCode(
    credit_code: "ABCDEF"
  ) {
      status
      message
  }
}
MUTATION;
        $this->graphQlMutation($mutation, [], '', $this->getCustomerAuthHeaders($userName, $password));
    }

    /**
     * Test with provider
     *
     * @magentoConfigFixture default/customercredit/general/enable 1
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magestore/CustomercreditGraphQl/Test/_files/credit_codes.php
     *
     * @magentoDbIsolation disabled
     *
     * @dataProvider dataProvider
     *
     * @param string $body
     * @param string $expectedMessage
     * @param bool $expectedStatus
     * @throws \Exception
     */
    public function testStoreCreditWithDifferentStatus(
        string $body,
        string $expectedMessage,
        bool $expectedStatus
    ) {
        $userName = 'customer@example.com';
        $password = 'password';

        $mutation
            = <<<MUTATION
        $body
MUTATION;
        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthHeaders($userName, $password)
        );
        $this->assertEquals($expectedMessage, $response['msRedeemCreditCode']['message']);
        $this->assertEquals($expectedStatus, $response['msRedeemCreditCode']['status']);
    }

    /**
     * [
     *      GraphQl Request Body,
     *      Message,
     * ]
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider(): array
    {
        return [
            [/* Data set #1. Code is active */
                'mutation {
                    msRedeemCreditCode(
                        credit_code: "ABCDEF-VALID"
                        ) {
                        status
                        message
                    }
                }',
                'Code was redeemed successfully!',
                true
            ],
            [/* Data set #2. Code is canceled */
                'mutation {
                    msRedeemCreditCode(
                        credit_code: "ABCDEF-CANCEL"
                        ) {
                        status
                        message
                    }
                }',
                'Code was canceled.',
                false
            ]
        ];
    }

    /**
     * @param string $email
     * @param string $password
     * @return array
     */
    private function getCustomerAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
    }
}
