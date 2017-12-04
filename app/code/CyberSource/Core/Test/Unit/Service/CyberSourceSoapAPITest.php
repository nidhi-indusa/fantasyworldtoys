<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Phrase;

/**
 * Class CyberSourceSoapAPITest
 * @codingStandardsIgnoreStart
 */
class CyberSourceSoapAPITest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $soapClientMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $transactionBuilderMock;

    /**
     * @var \CyberSource\Core\Helper\RequestDataBuilder
     */
    protected $requestDataBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $curl;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $authSession;

    /**
     * @var \CyberSource\Core\Service\CyberSourceSoapAPI
     */
    protected $service;

    /**
     * @var \Magento\Payment\Model\InfoInterface
     */
    protected $payment;

    public function setUp()
    {
        $wsdlFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wsdl' . DIRECTORY_SEPARATOR . 'CyberSourceTransaction_1.134.wsdl';

        $this->scopeConfigMock = $this->getMock('Magento\Framework\App\Config\ScopeConfigInterface', [], [], '', false);
        $this->regionModelMock = $this
            ->getMockBuilder(\Magento\Directory\Model\Region::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMock('Monolog\Logger', ['error', 'info'], [], '', false);
        $this->soapClientMock = $this->getMockFromWsdl(
            $wsdlFile,
            'CyberSourceTransactionWS'
        );

        $this->curl = $this->getMock('Magento\Framework\HTTP\Client\Curl', ['post','getBody'], [], '', false);
        $this->authSession = $this->getMock('\Magento\Backend\Model\Auth\Session', [], [], '', false);
        $this->transactionBuilderMock = $this->getMock('\Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface', [], [], '', false);

        $configMock = $this->getMockBuilder(\CyberSource\Core\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->any())
            ->method('getDeveloperId')
            ->willReturn(12345);

        $contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperDataMock = $this->getMockBuilder(\Magento\Checkout\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $sessionManager = $this->getMockBuilder(\Magento\Framework\Session\SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->checkoutSessionMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->requestDataBuilder = $this->getMockBuilder(\CyberSource\Core\Helper\RequestDataBuilder::class)
            ->setConstructorArgs([
                'context' => $contextMock,
                'storeManager' => $storeManagerMock,
                'config' => $configMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'customerSession' => $sessionManager,
                'data' => $helperDataMock
            ])
            ->getMock();

        $this->requestDataBuilder
            ->method('wrapGatewayError')
            ->willReturn(__('error message'));
        
        // Set to isTestMode under AbstractConnection::handleWsdlEnvironment
        $this->scopeConfigMock->expects($this->at(0))
            ->method('getValue')
            ->with(
                \CyberSource\Core\Service\AbstractConnection::IS_TEST_MODE_CONFIG_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ->willReturn(true);

        $this->scopeConfigMock->expects($this->at(1))
            ->method('getValue')
            ->with(
                \CyberSource\Core\Service\AbstractConnection::TEST_WSDL_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ->willReturn($wsdlFile);

        // Setup credentials AbstractConnection::setupCredentials
        $this->scopeConfigMock->expects($this->at(2))
            ->method('getValue')
            ->with(
                \CyberSource\Core\Service\AbstractConnection::MERCHANT_ID_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ->willReturn('collinsharper');

        $this->scopeConfigMock->expects($this->at(3))
            ->method('getValue')
            ->with(
                \CyberSource\Core\Service\AbstractConnection::TRANSACTION_KEY_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ->willReturn('123456');

        $this->soapClientMock->expects($this->any())
            ->method('SoapClient')
            ->withAnyParameters()
            ->willReturnSelf();

        $this->service = new \CyberSource\Core\Service\CyberSourceSoapAPI(
            $this->scopeConfigMock,
            $this->loggerMock,
            $this->transactionBuilderMock,
            $this->requestDataBuilder,
            $this->curl,
            $this->authSession,
            $this->regionModelMock,
            $this->soapClientMock
        );

        $this->payment = $this->getMock(
            '\Magento\Sales\Model\Order\Payment',
            [
                'getOrder',
                'getQuoteId',
                'getAmountAuthorized',
                'getCcTransId',
                'getOrderCurrencyCode'
            ], [], '', false);

        $this->payment->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturnSelf();

        $this->payment
            ->method('getQuoteId')
            ->willReturn(1);

        $this->service->setPayment($this->payment);

    }

    public function testGetAmount()
    {
        $this->payment->expects($this->atLeastOnce())
            ->method('getAmountAuthorized')
            ->willReturn(10.0);

        $amount = $this->service->getAmount();
        $this->assertEquals(10.0, $amount);
    }

    public function testGetMerchantReferenceCode()
    {
        $this->assertEquals(null, $this->service->getMerchantReferenceCode());
    }

    public function testCaptureOrder()
    {
        $this->payment->expects($this->atLeastOnce())
            ->method('getCcTransId')
            ->willReturn('ABCDE12345');

        $this->payment->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturnSelf();

        $this->payment->expects($this->atLeastOnce())
            ->method('getOrderCurrencyCode')
            ->willReturn('CAD');

        $this->soapClientMock
            ->method('runTransaction')
            ->willReturn((object)['reasonCode' => 100]);

        $result = $this->service->captureOrder(10);
        $this->assertEquals($result->reasonCode, 100);

        $this->loggerMock->expects($this->never())->method('error');
    }

    public function testCaptureOrderFail()
    {
        $this->payment->expects($this->atLeastOnce())
            ->method('getCcTransId')
            ->willReturn('ABCDE12345');

        $this->payment->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturnSelf();

        $this->payment->expects($this->atLeastOnce())
            ->method('getOrderCurrencyCode')
            ->willReturn('CAD');

        $this->soapClientMock->expects($this->once())
            ->method('runTransaction')
            ->willThrowException(new \Exception());

        $result = $this->service->captureOrder(10);

        $this->assertNull($result);
    }

    /**
     * @dataProvider tokenDataProvider
     */
    public function testTokenPayment($tokenData, $dmEnabled, $isCaptureRequest, $quote, $amount, $reasonCode, $decision)
    {

        $expectedResult = (object)[
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

        $this->requestDataBuilder->expects($this->once())
            ->method('buildTokenPaymentData')
            ->willReturn($expectedResult);

        $expectedResponse = (object)[
            'reasonCode' => $reasonCode,
            'decision' => $decision
        ];

        $this->soapClientMock->expects($this->once())
            ->method('runTransaction')
            ->willReturn($expectedResponse);

        if ($reasonCode !== 100) {
            $this->requestDataBuilder->expects($this->once())
                ->method('wrapGatewayError')
                ->with("Unable to place order")
                ->willReturn(__('Gateway error: Unable to place order'));

            $this->setExpectedException(\Magento\Framework\Exception\LocalizedException::class, __('Gateway error: Unable to place order'));
            $this->service->tokenPayment($tokenData, $dmEnabled, $isCaptureRequest, $quote, $amount);
        }

        if ($reasonCode === 100) {
            $response = $this->service->tokenPayment($tokenData, $dmEnabled, $isCaptureRequest, $quote, $amount);
            $this->assertEquals($expectedResponse, $response);
        }
    }
    
    /**
     * @dataProvider tokenDataProvider
     */
    public function testTokenPaymentFail($tokenData, $dmEnabled, $isCaptureRequest, $quote, $amount, $reasonCode, $decision)
    {

        $expectedResult = (object)[
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

        $this->requestDataBuilder->expects($this->once())
            ->method('buildTokenPaymentData')
            ->willReturn($expectedResult);

        $this->soapClientMock->expects($this->any())
            ->method('runTransaction')
            ->willThrowException(new \SoapFault('error', 'test'));

        $response = $this->service->tokenPayment($tokenData, $dmEnabled, $isCaptureRequest, $quote, $amount);
        $this->assertNull($response);
    }

    /**
     * Data provider for testTokenPayment
     *
     * @return array
     */
    public function tokenDataProvider()
    {
        $tokenMock = $this->getMock('\CyberSource\Core\Model\Token', [], [], '', false);

        /**
         * $tokenData
         * $dmEnabled
         * $isCaptureRequest
         * $quote
         * $amount
         * $reasonCode
         * $decision
         */
        return [
            [$tokenMock, true, false, null, null, 101, 'ACCEPTED'],
            [$tokenMock, true, false, null, null, 100, 'ACCEPTED'],
        ];

    }
    
    public function testRetrieveProfile()
    {
        $this->soapClientMock->expects($this->once())
            ->method('runTransaction');
        $this->service->retrieveProfile(1, 1);
    }

    public function testRetrieveProfileFail()
    {
        $this->soapClientMock->expects($this->once())
            ->method('runTransaction')
            ->willThrowException(new \SoapFault("Server", "test"));

        $this->loggerMock->expects($this->once())
            ->method('error');

        $this->setExpectedException(\Magento\Framework\Exception\LocalizedException::class, __("test"));

        $this->service->retrieveProfile(1, 1);
    }

    public function testConvertToProfile()
    {
        $data = [
            'merchant_id' => 1,
            'quote_id' => 1,
            'request_id' => 1
        ];

        $this->soapClientMock->expects($this->once())
            ->method('runTransaction')
            ->willReturn($data);

        $this->assertEquals($this->service->convertToProfile($data), $data);
    }

    public function testConvertToProfileFail()
    {
        $data = [
            'merchant_id' => 1,
            'quote_id' => 1,
            'request_id' => 1
        ];

        $this->soapClientMock->expects($this->once())
            ->method('runTransaction')
            ->willThrowException(new \Exception("test"));

        $this->loggerMock->expects($this->once())
            ->method('error');

        $this->assertEquals($this->service->convertToProfile($data), null);
    }
    

    /**
     * @dataProvider reverseVoidRefundDataProvider
     */
    public function testReverseOrderPayment($reasonCode, $merchantReferenceCode, $requestID, $decision)
    {
        $expectedResponse = (object)[
            'reasonCode' => $reasonCode,
            'merchantReferenceCode' => $merchantReferenceCode,
            'requestID' => $requestID,
            'decision' => $decision
        ];


        $order = $this->getMock('\Magento\Sales\Model\Order', ['getQuoteId'], [], '', false);
        $order
            ->method('getQuoteId')
            ->willReturn(1);

        $paymentMock = $this->getMock('\Magento\Sales\Model\Order\Payment', ['getOrder', 'addTransactionCommentsToOrder'], [], '', false);

        $paymentMock->expects($this->any())
            ->method('addTransactionCommentsToOrder')
            ->willReturnSelf();

        $paymentMock->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturn($order);

        $this->payment = $paymentMock;

        $this->transactionBuilderMock->expects($this->any())
            ->method('setPayment')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setOrder')
            ->withAnyParameters()
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setTransactionId')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setAdditionalInformation')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setFailSafe')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('build')
            ->willReturnSelf();

        $this->soapClientMock->expects($this->any())
            ->method('runTransaction')
            ->willReturn($expectedResponse);

        $this->service->setPayment($paymentMock);
        $result = $this->service->reverseOrderPayment();

        $this->assertEquals($result->reasonCode, $reasonCode);

        if ($reasonCode === 999) {
            $result = $this->service->reverseOrderPayment();
            $this->assertEquals($result->reasonCode, $reasonCode);
        }
    }

    /**
     * @dataProvider reverseVoidRefundDataProvider
     */
    public function testVoidOrderPayment($reasonCode, $merchantReferenceCode, $requestID, $decision)
    {
        $expectedResponse = (object)[
            'reasonCode' => $reasonCode,
            'merchantReferenceCode' => $merchantReferenceCode,
            'requestID' => $requestID,
            'decision' => $decision
        ];


        $order = $this->getMock('\Magento\Sales\Model\Order', ['getQuoteId'], [], '', false);
        $order
            ->method('getQuoteId')
            ->willReturn(1);

        $paymentMock = $this->getMock('\Magento\Sales\Model\Order\Payment', ['getOrder', 'addTransactionCommentsToOrder'], [], '', false);

        $paymentMock->expects($this->any())
            ->method('addTransactionCommentsToOrder')
            ->willReturnSelf();

        $paymentMock->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturn($order);

        $this->payment = $paymentMock;

        $this->transactionBuilderMock->expects($this->any())
            ->method('setPayment')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setOrder')
            ->withAnyParameters()
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setTransactionId')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setAdditionalInformation')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setFailSafe')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('build')
            ->willReturnSelf();

        $this->soapClientMock->expects($this->any())
            ->method('runTransaction')
            ->willReturn($expectedResponse);

        $this->service->setPayment($paymentMock);
        $result = $this->service->voidOrderPayment();

        $this->assertEquals($result->reasonCode, $reasonCode);
    }

    /**
     * @dataProvider reverseVoidRefundDataProvider
     */
    public function testVoidOrderPaymentFail($reasonCode, $merchantReferenceCode, $requestID, $decision)
    {
        $expectedResponse = (object)[
            'reasonCode' => $reasonCode,
            'merchantReferenceCode' => $merchantReferenceCode,
            'requestID' => $requestID,
            'decision' => $decision
        ];


        $order = $this->getMock('\Magento\Sales\Model\Order', ['getQuoteId'], [], '', false);
        $order
            ->method('getQuoteId')
            ->willReturn(1);

        $paymentMock = $this->getMock('\Magento\Sales\Model\Order\Payment', ['getOrder', 'addTransactionCommentsToOrder'], [], '', false);

        $paymentMock->expects($this->any())
            ->method('addTransactionCommentsToOrder')
            ->willReturnSelf();

        $paymentMock->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturn($order);

        $this->payment = $paymentMock;

        $this->transactionBuilderMock->expects($this->any())
            ->method('setPayment')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setOrder')
            ->withAnyParameters()
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setTransactionId')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setAdditionalInformation')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setFailSafe')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('build')
            ->willReturnSelf();

        $this->soapClientMock->expects($this->any())
            ->method('runTransaction')
            ->willThrowException(new \Exception());

        $this->service->setPayment($paymentMock);
        $result = $this->service->voidOrderPayment();

        $this->assertNull($result);
    }

    /**
     * @dataProvider reverseVoidRefundDataProvider
     */
    public function testRefundOrderPayment($reasonCode, $merchantReferenceCode, $requestID, $decision)
    {
        $expectedResponse = (object)[
            'reasonCode' => $reasonCode,
            'merchantReferenceCode' => $merchantReferenceCode,
            'requestID' => $requestID,
            'decision' => $decision
        ];


        $order = $this->getMock('\Magento\Sales\Model\Order', [
            'getQuoteId',
            'getAllItems'
        ], [], '', false);
        $order
            ->method('getQuoteId')
            ->willReturn(1);

        $paymentMock = $this->getMock('\Magento\Sales\Model\Order\Payment', ['getOrder', 'addTransactionCommentsToOrder'], [], '', false);

        $paymentMock->expects($this->any())
            ->method('addTransactionCommentsToOrder')
            ->willReturnSelf();

        $paymentMock->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturn($order);

        $this->payment = $paymentMock;

        $this->transactionBuilderMock->expects($this->any())
            ->method('setPayment')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setOrder')
            ->withAnyParameters()
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setTransactionId')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setAdditionalInformation')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setFailSafe')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('build')
            ->willReturnSelf();

        $this->soapClientMock->expects($this->any())
            ->method('runTransaction')
            ->willReturn($expectedResponse);

        $this->service->setPayment($paymentMock);

        $item = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $order->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$item]);
        
        $result = $this->service->refundOrderPayment(10);

        if ($reasonCode === 100) {
            $this->assertTrue($result);
        }
    }

    /**
     * @dataProvider reverseVoidRefundDataProvider
     */
    public function testRefundOrderPaymentFail($reasonCode, $merchantReferenceCode, $requestID, $decision)
    {
        $expectedResponse = (object)[
            'reasonCode' => $reasonCode,
            'merchantReferenceCode' => $merchantReferenceCode,
            'requestID' => $requestID,
            'decision' => $decision
        ];


        $order = $this->getMock('\Magento\Sales\Model\Order', ['getQuoteId', 'getAllItems'], [], '', false);
        $order
            ->method('getQuoteId')
            ->willReturn(1);

        $paymentMock = $this->getMock('\Magento\Sales\Model\Order\Payment', ['getOrder', 'addTransactionCommentsToOrder'], [], '', false);

        $paymentMock->expects($this->any())
            ->method('addTransactionCommentsToOrder')
            ->willReturnSelf();

        $paymentMock->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturn($order);

        $this->payment = $paymentMock;

        $this->transactionBuilderMock->expects($this->any())
            ->method('setPayment')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setOrder')
            ->withAnyParameters()
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setTransactionId')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setAdditionalInformation')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('setFailSafe')
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->any())
            ->method('build')
            ->willReturnSelf();

        $this->soapClientMock->expects($this->any())
            ->method('runTransaction')
            ->willThrowException(new \Exception());

        $this->service->setPayment($paymentMock);
        
        $item = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $order->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$item]);
        
        $result = $this->service->refundOrderPayment(10);

        $this->assertFalse($result);
    }

    /**
     * Data provider for testReverseOrderPayment
     *
     * @return array
     */
    public function reverseVoidRefundDataProvider()
    {
        // "merchantReferenceCode",
        // "requestID",
        // "decision",
        // "reasonCode"

        return [
            [100, 1, 1, "ACCEPTED"],
            [150, 1, 1, "ACCEPTED"],
            [999, 1, 1, "ACCEPTED"]
        ];

    }


    public function testReverseOrderPaymentFail()
    {
        $this->soapClientMock->expects($this->exactly(2))
            ->method('runTransaction')
            ->willThrowException(new \Exception("test"));

        $this->loggerMock->expects($this->exactly(2))
            ->method('error');

        $this->service->reverseOrderPayment();
    }

    public function testIsSuccessfullyVoided()
    {
        $this->assertFalse($this->service->isSuccessfullyVoided());
    }


    public function testIsSuccessfullyReversed()
    {
        $this->assertFalse($this->service->isSuccessfullyReversed());
    }

    public function testGetTransactionStatus()
    {
        $this->curl->expects($this->once())
            ->method('getBody')
            ->willReturn('<?xml version="1.0" encoding="UTF-8"?><body><Requests><Request>xyz</Request></Requests></body>');

        $this->curl->expects($this->once())
            ->method('post')
            ->willReturnSelf();

        $status = $this->service->getTransactionStatus(1, new \DateTime('now'));

        $this->assertEquals($status, "ok");
    }

    public function testGetTransactionStatusFail()
    {
        $this->curl->expects($this->once())
            ->method('post')
            ->willReturnSelf();

        $this->curl->expects($this->once())
            ->method('getBody')
            ->willThrowException(new \Exception("test"));

        $status = $this->service->getTransactionStatus(1, new \DateTime('now'));

        $this->assertEquals($status, "error");
    }

    public function testGetListOfBanks()
    {
        $response = (object)
        [
             'reasonCode' => 100,
             'apOptionsReply' => (object)
             [
                 'option' => (object) [
                     (object) [
                         'id' => 1,
                         'name' => 'test'
                     ],
                 ]
             ]
        ];

        $this->soapClientMock
            ->method('runTransaction')
            ->willReturn($response);

        $response = $this->service->getListOfBanks(1, 1);
        $this->assertEquals($response, []);
    }

    public function testGetListOfBanksFail()
    {
        $this->soapClientMock
            ->method('runTransaction')
            ->willThrowException(new \Exception('test'));

        $response = $this->service->getListOfBanks(1, 1);
        $this->assertEmpty($response);
    }

    /**
     * @dataProvider bankTransferSaleDataProvider
     */
    public function testBankTransferSale($bankCode, $hasResult, $throwException)
    {
        $addressMock = $this->getMock('Magento\Quote\Model\Quote\Address', ['getGrandTotal', 'getBillingAddress'], [], '', false);
        $quoteMock = $this->getMock('Magento\Quote\Model\Quote', ['getGrandTotal', 'getBillingAddress'], [], '', false);
        $quoteMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($addressMock);
        $storeMock = $this->getMock('Magento\Store\Model\Store', ['getBaseUrl'], [], '', false);
        $storeMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn("http://localhost/");

        $response = $this->service->bankTransferSale($quoteMock, 1, $storeMock, $bankCode, 1);

        $this->assertEquals($response, []);


        if ($throwException) {
            $this->soapClientMock
                ->method('runTransaction')
                ->willThrowException(new \Exception('test'));

            $this->service->bankTransferSale($quoteMock, 1, $storeMock, $bankCode, 1);
        }

        if ($hasResult) {

            $result = (object) [
                'reasonCode' => 100,
                'apSaleReply' => (object) [
                    'merchantURL' => 'merchant_url'
                ]
            ];

            $this->soapClientMock
                ->method('runTransaction')
                ->willReturn($result);

            $response = $this->service->bankTransferSale($quoteMock, 1, $storeMock, $bankCode, 1);
            $this->assertEquals(
                $response,
                []
            );
        }
    }

    /**
     * Data provider for bankTransferSale
     *
     * @return array
     */
    public function bankTransferSaleDataProvider()
    {
        return [
            ['sofort', false, false],
            ['bancontact', true, false],
            ['ideal', false, true]
        ];

    }

    /**
     * @dataProvider checkBankTransferSaleDataProvider
     */
    public function testCheckBankTransferStatus($throwException)
    {
        $response = $this->service->checkBankTransferStatus(1,1, 1, 'ideal');
        $this->assertNull($response);

        if ($throwException) {
            $this->soapClientMock
                ->method('runTransaction')
                ->willThrowException(new \Exception('test'));

            $this->service->checkBankTransferStatus(1,1, 1, 'ideal');
        }
    }

    /**
     * Data provider for testCheckBankTransferStatus
     *
     * @return array
     */
    public function checkBankTransferSaleDataProvider()
    {
        return [
            [false],
            [true]
        ];

    }

    public function testGetTaxes()
    {
        $addressMock = $this->getMock('Magento\Quote\Model\Quote\Address', ['getGrandTotal', 'getBillingAddress', 'getCountry'], [], '', false);

        $addressMock
            ->method('getCountry')
            ->willReturn('CA');

        $quoteMock = $this->getMock(
                'Magento\Quote\Model\Quote', 
                [
                    'getGrandTotal', 
                    'getBillingAddress',
                    'getShippingAddress',
                    'getAllItems',
                    'getAddressesCollection',
                    'reserveOrderId',
                    'getReservedOrderId'
                ], 
                [], '', false);
        $quoteMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($addressMock);
        $quoteMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($addressMock);

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteItemConfigurable = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteItemConfigurable->expects($this->any())
            ->method('getProductType')
            ->willReturn('configurable');
        $quoteItemConfigurable
            ->method('getProduct')
            ->willReturn($product);

        $quoteItemSimple = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteItemSimple->expects($this->any())
            ->method('getProductType')
            ->willReturn('simple');
        $quoteItemSimple
            ->method('getProduct')
            ->willReturn($product);
        
        $quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$quoteItemConfigurable, $quoteItemSimple]);

        $quoteMock->expects($this->any())
            ->method('getItemsQty')
            ->willReturn(1);
        
        $quoteMock->method('reserveOrderId')
            ->willReturn($quoteMock);

        $this->assertEquals('', $this->service->getTaxes(1, $quoteMock));
    }

    public function testGetTaxesFail()
    {
        $addressMock = $this->getMock('Magento\Quote\Model\Quote\Address', ['getGrandTotal', 'getBillingAddress', 'getCountry'], [], '', false);

        $addressMock
            ->method('getCountry')
            ->willReturn('CA');

        $quoteMock = $this->getMock(
                'Magento\Quote\Model\Quote', 
                [
                    'getGrandTotal', 
                    'getBillingAddress',
                    'getShippingAddress',
                    'getAllItems',
                    'getAddressesCollection',
                    'reserveOrderId',
                    'getReservedOrderId'
                ], 
                [], '', false);
        $quoteMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($addressMock);
        $quoteMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($addressMock);
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItemConfigurable = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteItemConfigurable->expects($this->any())
            ->method('getProductType')
            ->willReturn('configurable');
        $quoteItemConfigurable
            ->method('getProduct')
            ->willReturn($product);
        
        $quoteItemSimple = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteItemSimple->expects($this->any())
            ->method('getProductType')
            ->willReturn('simple');
        $quoteItemSimple
            ->method('getProduct')
            ->willReturn($product);
        
        $quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$quoteItemConfigurable, $quoteItemSimple]);

        $quoteMock->expects($this->any())
            ->method('getItemsQty')
            ->willReturn(1);

        $this->soapClientMock
            ->method('runTransaction')
            ->willThrowException(new \Exception('test'));
        
        $quoteMock
            ->method('reserveOrderId')
            ->willReturn($quoteMock);
        
        $this->service->getTaxes(1, $quoteMock);
    }

    public function testCheckAddress()
    {
        $billingAddressMock = $this->getMock('Magento\Quote\Model\Quote\Address', ['getGrandTotal', 'getBillingAddress', 'getCountry'], [], '', false);
        $billingAddressMock->expects($this->once())
            ->method('getCountry')
            ->willReturn('CA');

        $shippingAddress = [
            'city' => null,
            'country' => 'CA',
            'firstname' => null,
            'lastname' => null,
            'postcode' => null,
            'region_code' => null,
            'street1' => null,
            'street2' => null,
            'telephone' => null
        ];

        $result = $this->service->checkAddress(1, 1, $shippingAddress, $billingAddressMock);
        $this->assertNull($result);
    }

    public function testCheckAddressFail()
    {
        $billingAddressMock = $this->getMock('Magento\Quote\Model\Quote\Address', ['getGrandTotal', 'getBillingAddress', 'getCountry'], [], '', false);
        $billingAddressMock->expects($this->once())
            ->method('getCountry')
            ->willReturn('CA');

        $shippingAddress = [
            'city' => null,
            'country' => 'CA',
            'firstname' => null,
            'lastname' => null,
            'postcode' => null,
            'region_code' => null,
            'street1' => null,
            'street2' => null,
            'telephone' => null
        ];

        $this->soapClientMock->expects($this->once())
            ->method('runTransaction')
            ->willThrowException(new \Exception('test'));

        $this->service->checkAddress(1, 1, $shippingAddress, $billingAddressMock);
    }
}
