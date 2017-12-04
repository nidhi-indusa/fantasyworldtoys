<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Helper;

use Magento\Framework\Phrase;

/**
 * Class DataTest
 * @package CyberSource\Core\Test\Unit\Helper
 * @covers \CyberSource\Core\Helper\Data
 * @covers \CyberSource\Core\Helper\AbstractDataBuilder
 */
class DataTest extends \PHPUnit_Framework_TestCase
{
    private $dataHelper;

    private $helper;

    private $adminOrder;

    private $urlBuilder;

    public function setUp()
    {
        $this->helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->adminOrder = $this->getMockBuilder(\Magento\Sales\Model\AdminOrder\Create::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();

        $this->urlBuilder = $this->getMock(\Magento\Backend\Model\UrlInterface::class);

        $this->urlBuilder
            ->expects(static::any())
            ->method('getUrl')
            ->with('adminhtml/order_cybersource/payment')
            ->willReturn('adminhtml/order_cybersource/payment');

        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            [
                'order' => $this->adminOrder,
                'backendUrl' => $this->urlBuilder
            ]
        );
    }

    /**
     * @covers \CyberSource\Core\Helper\Data::getPaymentProcessorName
     */
    public function testGetPaymentProcessorName()
    {
        static::assertEquals(
            'CyberSource Latin American Processing',
            $this->dataHelper->getPaymentProcessorName('cybersource_latin')
        );
    }

    public function testGetGatewayConfig()
    {
        static::assertInstanceOf(
            '\CyberSource\Core\Model\Config',
            $this->dataHelper->getGatewayConfig()
        );
    }

    public function testGetQuote()
    {
        $this->adminOrder->expects(static::once())
            ->method('getQuote')
            ->willReturn(
                $this->helper->getObject('\Magento\Quote\Model\Quote')
            );

        static::assertInstanceOf(
            '\Magento\Quote\Model\Quote',
            $this->dataHelper->getQuote()
        );
    }

    public function testGetUrl()
    {
        $url = $this->dataHelper->getPlaceOrderAdminUrl();
        $this->assertEquals(
            $url,
            'adminhtml/order_cybersource/payment'
        );
    }

    public function testIsMultipleCapture()
    {
        $data = $this->dataHelper->isMultipleCapture('aibms');
        $this->assertTrue($data);

        $data = $this->dataHelper->isMultipleCapture('cybersource_latin');
        $this->assertFalse($data);
    }

    public function testGetAdditionalData()
    {
        $data = $this->dataHelper->getAdditionalData(['reasonCode' => 100]);
        $this->assertEquals($data, ['reasonCode' => 100]);

        $data = $this->dataHelper->getAdditionalData(['xyz' => 123]);
        $this->assertEmpty($data);
    }

    public function testSign()
    {
        $params = [
            "signed_field_names" => "test1,test2,test3",
            "test1" => 1,
            "test2" => 2,
            "test3" => 3
        ];

        $hash = $this->dataHelper->sign($params, 1234);

        $this->assertEquals(
            $hash,
            "D1K0Jvic06yzf03sumSa0a113JJyiT3jrxD+vpBCbGk="
        );
    }

    public function testBuildDataToSign()
    {
        $params = [
            "signed_field_names" => "test1,test2,test3",
            "test1" => 1,
            "test2" => 2,
            "test3" => 3
        ];

        $data = $this->dataHelper->buildDataToSign($params);

        $this->assertInternalType("string", $data);
        $this->assertCount(3, explode(",", $data));
        $this->assertEquals("test1=1,test2=2,test3=3", $data);
    }

    /**
     * Assert that customer isLoggedIn and return customer checkout method
     */
    public function testGetCheckoutMethodCustomer()
    {
        $customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerSessionMock->expects(static::any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->expects(static::any())
            ->method('getCheckoutMethod')
            ->willReturn(false);

        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            [
                'order' => $this->adminOrder,
                'backendUrl' => $this->urlBuilder,
                'customerSession' => $customerSessionMock
            ]
        );

        $checkoutMethod = $this->dataHelper->getCheckoutMethod($quoteMock);

        $this->assertEquals(
            \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER,
            $checkoutMethod
        );
    }

    public function testGetCheckoutMethodGuest()
    {
        $customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerSessionMock->expects(static::any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCheckoutMethod'])
            ->getMock();

        $quoteMock->expects($this->at(0))
            ->method('getCheckoutMethod')
            ->willReturn(false);

        $quoteMock->expects($this->at(1))
            ->method('getCheckoutMethod')
            ->willReturn(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);

        $quoteMock->expects(static::any())
            ->method('setCheckoutMethod')
            ->with(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST)
            ->willReturnSelf();

        $checkoutHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowedGuestCheckout'])
            ->getMock();

        $checkoutHelperMock->expects(static::any())
            ->method('isAllowedGuestCheckout')
            ->willReturn(true);

        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            [
                'order' => $this->adminOrder,
                'backendUrl' => $this->urlBuilder,
                'customerSession' => $customerSessionMock,
                'checkoutHelper' => $checkoutHelperMock
            ]
        );

        $checkoutMethod = $this->dataHelper->getCheckoutMethod($quoteMock);

        $this->assertEquals(
            \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST,
            $checkoutMethod
        );
    }

    public function testGetCheckoutMethodRegister()
    {
        $customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerSessionMock->expects(static::any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCheckoutMethod'])
            ->getMock();

        $quoteMock->expects($this->at(0))
            ->method('getCheckoutMethod')
            ->willReturn(false);

        $quoteMock->expects($this->at(1))
            ->method('getCheckoutMethod')
            ->willReturn(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);

        $quoteMock->expects(static::any())
            ->method('setCheckoutMethod')
            ->with(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER)
            ->willReturnSelf();

        $checkoutHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowedGuestCheckout'])
            ->getMock();

        $checkoutHelperMock->expects(static::any())
            ->method('isAllowedGuestCheckout')
            ->willReturn(false);

        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            [
                'order' => $this->adminOrder,
                'backendUrl' => $this->urlBuilder,
                'customerSession' => $customerSessionMock,
                'checkoutHelper' => $checkoutHelperMock
            ]
        );

        $checkoutMethod = $this->dataHelper->getCheckoutMethod($quoteMock);

        $this->assertEquals(
            \Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER,
            $checkoutMethod
        );
    }

    public function testGetCheckoutMethod()
    {
        $customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerSessionMock->expects(static::any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCheckoutMethod'])
            ->getMock();

        $quoteMock->expects($this->at(0))
            ->method('getCheckoutMethod')
            ->willReturn(true);

        $quoteMock->expects($this->at(1))
            ->method('getCheckoutMethod')
            ->willReturn('given_method');

        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            [
                'order' => $this->adminOrder,
                'backendUrl' => $this->urlBuilder,
                'customerSession' => $customerSessionMock
            ]
        );

        $checkoutMethod = $this->dataHelper->getCheckoutMethod($quoteMock);

        $this->assertEquals(
            'given_method',
            $checkoutMethod
        );
    }

    public function testPrepareGuestQuote()
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quote->expects(static::any())
            ->method('setCustomerId')
            ->with(null)
            ->willReturnSelf();

        $quote->expects(static::any())
            ->method('setCustomerEmail')
            ->with('test@test.com')
            ->willReturnSelf();

        $address = $this->getMockBuilder(\Magento\Quote\Api\Data\AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $address->expects(static::once())
            ->method('getEmail')
            ->willReturn('test@test.com');

        $quote->expects(static::once())
            ->method('getBillingAddress')
            ->willReturn($address);


        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            []
        );

        $this->assertNull($this->dataHelper->prepareGuestQuote($quote));
    }

    public function testGetSignedFields()
    {
        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            []
        );

        $isToken = false;
        $isSilent = false;
        $manageToken = false;

        $fields = $this->dataHelper->getSignedFields($isToken, $isSilent, $manageToken);

        $this->assertInternalType("string", $fields);
        $this->assertRegExp('/,/', $fields);
        $this->assertArrayHasKey('tax_amount', array_flip(explode(",", $fields)));
        $this->assertArrayHasKey('card_number', array_flip(explode(",", $fields)));
        $this->assertArrayNotHasKey('bill_to_email', array_flip(explode(",", $fields)));

        $isToken = true;
        $isSilent = false;
        $manageToken = false;

        $fields = $this->dataHelper->getSignedFields($isToken, $isSilent, $manageToken);

        $this->assertInternalType("string", $fields);
        $this->assertRegExp('/,/', $fields);
        $this->assertArrayNotHasKey('tax_amount', array_flip(explode(",", $fields)));
        $this->assertArrayNotHasKey('card_number', array_flip(explode(",", $fields)));
        $this->assertArrayNotHasKey('bill_to_email', array_flip(explode(",", $fields)));

        $isToken = false;
        $isSilent = true;
        $manageToken = false;

        $fields = $this->dataHelper->getSignedFields($isToken, $isSilent, $manageToken);

        $this->assertInternalType("string", $fields);
        $this->assertRegExp('/,/', $fields);
        $this->assertArrayNotHasKey('tax_amount', array_flip(explode(",", $fields)));
        $this->assertArrayNotHasKey('card_number', array_flip(explode(",", $fields)));
        $this->assertArrayHasKey('bill_to_email', array_flip(explode(",", $fields)));
        $this->assertArrayHasKey('payer_auth_enroll_service_run', array_flip(explode(",", $fields)));

        $isToken = false;
        $isSilent = false;
        $manageToken = true;

        $fields = $this->dataHelper->getSignedFields($isToken, $isSilent, $manageToken);

        $this->assertInternalType("string", $fields);
        $this->assertRegExp('/,/', $fields);
        $this->assertArrayNotHasKey('tax_amount', array_flip(explode(",", $fields)));
        $this->assertArrayNotHasKey('card_number', array_flip(explode(",", $fields)));
        $this->assertArrayNotHasKey('payer_auth_enroll_service_run', array_flip(explode(",", $fields)));
        $this->assertArrayHasKey('bill_to_email', array_flip(explode(",", $fields)));
    }

    public function testGetUnsignedFields()
    {
        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            []
        );

        $params = [
            "signed_field_names" => "test1,test2,test3",
            "test1" => 1,
            "test2" => 2,
            "test3" => 3
        ];
        ;

        $fields = $this->dataHelper->getUnsignedFields($params);

        $this->assertEquals('signed_field_names', $fields);
    }

    public function testSetUpCredentials()
    {
        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            []
        );

        $this->dataHelper->setUpCredentials('test1', 'test2');

        $this->assertNotNull($this->dataHelper->merchantId);
        $this->assertNotNull($this->dataHelper->transactionKey);

        $this->assertEquals($this->dataHelper->merchantId, 'test1');
        $this->assertEquals($this->dataHelper->transactionKey, 'test2');
    }

    public function testFormatAmount()
    {
        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            []
        );

        $formatted = $this->dataHelper->formatAmount(2);

        $this->assertInternalType("string", $formatted);
        $this->assertEquals("2.0", $formatted);
    }

    public function testGetCcTypes()
    {
        $this->dataHelper = $this->helper->getObject(
            'CyberSource\Core\Helper\Data',
            []
        );

        $ccTypes = $this->dataHelper->getCcTypes();

        $this->assertInternalType("array", $ccTypes);
        $this->assertArrayHasKey("VI", $ccTypes);
        $this->assertArrayNotHasKey("vi", $ccTypes);
        $this->assertCount(4, $ccTypes);
        $this->assertInternalType("array", $ccTypes["VI"]);
        $this->assertArrayHasKey("code", $ccTypes["VI"]);
    }

    public function testGetCardName()
    {
        $cardName = $this->dataHelper->getCardName("");
        $this->assertEmpty($cardName);

        $cardName = $this->dataHelper->getCardName("001");
        $this->assertEquals("Visa", $cardName);

        $cardName = $this->dataHelper->getCardName("999");
        $this->assertEmpty($cardName);
    }

    public function testWrapGatewayError()
    {
        $error = $this->dataHelper->wrapGatewayError("TEST");
        $this->assertInstanceOf(Phrase::class, $error);
        $this->assertEquals($error->getArguments()[0], "TEST");
    }

    public function testGetPayerAuthenticationData()
    {
        $request = ['test'];
        $data = $this->dataHelper->getPayerAuthenticationData($request);
        $this->assertEmpty($data);

        $request = ['payer_authentication_name' => 'test'];
        $data = $this->dataHelper->getPayerAuthenticationData($request);
        $this->assertInternalType("array", $data);
        $this->assertArrayHasKey("payer_authentication_name", $data);

        $request = ['payer_authentication_proof_xml' => 'test'];
        $data = $this->dataHelper->getPayerAuthenticationData($request);
        $this->assertEmpty($data);
        $this->assertArrayNotHasKey("payer_authentication_name", $data);
    }
}
