<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Helper;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class RequestDataBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \CyberSource\Core\Helper\RequestDataBuilder
     */
    private $requestDataBuilderHelper;
    private $helper;

    public function setUp()
    {
        $this->helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $storeManagerMock = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            ['getStore', 'getId']
        );

        $storeInterfaceMock = $this->getMock(StoreInterface::class);

        $storeInterfaceMock->expects(static::any())
            ->method('getId')
            ->willReturn(1);

        $storeManagerMock->expects(static::any())
            ->method('getStore')
            ->willReturn($storeInterfaceMock);

        $this->checkoutSessionMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->requestDataBuilderHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\RequestDataBuilder',
            [
                'storeManager' => $storeManagerMock,
                'checkoutSession' => $this->checkoutSessionMock
            ]
        );
    }

    public function testGetStoreId()
    {
        $storeId = $this->requestDataBuilderHelper->getStoreId();
        $this->assertEquals(1, $storeId);
    }

    public function testBuildTokenPaymentData()
    {
        $config = $this->getMockBuilder(\CyberSource\Core\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects(static::any())
            ->method('getMerchantId')
            ->willReturn(123);

        $config->expects(static::any())
            ->method('getTransactionKey')
            ->willReturn('abc123');

        $config->expects(static::any())
            ->method('getDeveloperId')
            ->willReturn(12345);


        $orderItemMock = $this->getMock(\Magento\Quote\Api\Data\CartItemInterface::class);
        $orderItemMock->expects(static::any())
            ->method('getPrice')
            ->willReturn(36);

        $orderItemMock->expects(static::any())
            ->method('getName')
            ->willReturn('Test Item');

        $orderItemMock->expects(static::any())
            ->method('getSku')
            ->willReturn('sku1234');

        $orderItemMock->expects(static::any())
            ->method('getQty')
            ->willReturn(1);

        $payment = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteId'])
            ->getMock();

        $payment->expects(static::any())
            ->method('getQuoteId')
            ->willReturn(1);

        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getPayment',
                    'getQuoteCurrencyCode',
                    'getGrandTotal',
                    'getAllVisibleItems',
                    'getAllItems',
                    'getStoreId',
                    'getStore'
                ]
            )
            ->getMock();

        $quote->expects(static::any())
            ->method('getPayment')
            ->willReturn($payment);

        $quote->expects(static::any())
            ->method('getQuoteCurrencyCode')
            ->willReturn("CAD");

        $quote->expects(static::any())
            ->method('getGrandTotal')
            ->willReturn(10.0);

        $quote->expects(static::any())
            ->method('getAllVisibleItems')
            ->willReturn([$orderItemMock]);

        $quote->expects(static::any())
            ->method('getAllItems')
            ->willReturn([$orderItemMock]);

        $this->requestDataBuilderHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\RequestDataBuilder',
            [
                'config' => $config
            ]
        );

        $expectedResult = (object) [
            'merchantID' => 123,
            'partnerSolutionID' => 'T54H9OLO',
            'merchantReferenceCode' => 1,
            'clientLibrary' => 'PHP',
            'clientLibraryVersion' => phpversion(),
            'ccAuthService' => (object)
                [
                    'run' => 'true',
                    'authIndicator' => '',
                    'reconciliationID' => 1,
                    'commerceIndicator' => 'moto'
                ],
            'recurringSubscriptionInfo' => (object)
                [
                    'subscriptionID' => 12345,
                ],
            'purchaseTotals' => (object)
                [
                    'currency' => "CAD",
                    'grandTotalAmount' => '10.00',
                ],
            'item' => (object)
                [
                    'id' => 0,
                    'productName' => 'Test Item',
                    'productSKU' => 'sku1234',
                    'quantity' => 1,
                    'productCode' => 'default',
                    'unitPrice' => 36,
                ],
            'developerId' => 12345,
            'ccCaptureService' => (object)
            [
                'run' => "true",
            ],
            'card' => (object)
            [
                'cvNumber' => 123,
                'cvIndicator' => '1'
            ],
            'decisionManager' => (object)
            [
                'enabled' => 'false'
            ]
        ];

        $tokenData = [
            'payment_token' => 12345,
            'cvv' => 123
        ];

        $builtTokenData = $this->requestDataBuilderHelper->buildTokenPaymentData(
            $tokenData,
            $quote,
            true,
            null,
            false,
            true
        );

        $this->assertInternalType("object", $builtTokenData);
        $this->assertEquals($expectedResult, $builtTokenData);
    }

    public function testBuildTokenByTransaction()
    {
        $data['merchant_id'] = 123;
        $data['quote_id'] = 1;
        $data['request_id'] = 1;
        $data['ref_id'] = 1;

        $buildTokenTransactionData = $this->requestDataBuilderHelper->buildTokenByTransaction(
            $data
        );

        $expectedResult = (object) [
            'merchantID' => 123,
            'merchantReferenceCode' => 1,
            'clientLibrary' => 'PHP',
            'clientLibraryVersion' => phpversion(),
            'paySubscriptionCreateService' => (object)
                [
                    'run' => 'true',
                    'paymentRequestID' => 1,
                ],
            'recurringSubscriptionInfo' => (object)
                [
                    'frequency' => 'on-demand',
                ],
            'partnerSolutionID' => 'T54H9OLO'
        ];

        $this->assertInternalType('object', $buildTokenTransactionData);
        $this->assertEquals($expectedResult, $buildTokenTransactionData);
    }
}
