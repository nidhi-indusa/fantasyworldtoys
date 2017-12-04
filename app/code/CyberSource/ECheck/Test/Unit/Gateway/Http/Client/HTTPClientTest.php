<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Http\Client;

use CyberSource\ECheck\Gateway\Config\Config;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use CyberSource\ECheck\Gateway\Http\Client\HTTPClient;
use Psr\Log\LoggerInterface;

class HTTPClientTest extends \PHPUnit_Framework_TestCase
{
    const TXN_ID = 'fcd7f001e9274fdefb14bff91c799306';

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var ZendClient|\PHPUnit_Framework_MockObject_MockObject
     */
    private $zendClientMock;

    /**
     * @var Config |\PHPUnit_Framework_MockObject_MockObject
     */
    private $clientMock;

    private $counter = 0;
    
    public function setUp()
    {
        $this->zendClientMock = $this->getMock(ZendClient::class);
        $logger = $this->getMock(LoggerInterface::class);

        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects(static::any())
            ->method('isTestMode')
            ->willReturn(1);

        $configMock->expects(static::any())
            ->method('getServerUrl')
            ->willReturn('http://localhost');

        $configMock->expects(static::any())
            ->method('getMerchantUsername')
            ->willReturn('username');

        $configMock->expects(static::any())
            ->method('getMerchantPassword')
            ->willReturn('password');

        $httpClient = $this->getMockBuilder(ZendClientFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $httpClient->expects(static::any())
            ->method('create')
            ->willReturn($this->zendClientMock);

        $this->clientMock = new HTTPClient($httpClient, $configMock, $logger);
    }

    /**
     * @param array $expectedRequest
     * @param array $expectedResponse
     *
     * @dataProvider placeRequestDataProvider
     */
    public function testPlaceRequest(array $expectedRequest, array $expectedResponse)
    {
        $this->zendClientMock
            ->method('setParameterPost')
            ->willReturn($expectedRequest);

        $this->zendHttpResponseMock = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->zendHttpResponseMock->expects(static::any())
            ->method('getBody')
            ->willReturn(current($expectedResponse));

        $this->zendClientMock->expects(static::any())
            ->method('request')
            ->with(\Zend_Http_Client::POST)
            ->will($this->returnCallback(function ($param) {
                $this->counter++;
                if ($this->counter == 2) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('error'));
                } else {
                    return $this->zendHttpResponseMock;
                }
            }));

        /** @var TransferInterface|\PHPUnit_Framework_MockObject_MockObject $transferObject */
        $transferObject = $this->getMock(TransferInterface::class);
        $transferObject->method('getBody')->willReturn($expectedRequest);

        static::assertEquals(
            (array) (object) simplexml_load_string(current($expectedResponse)),
            $this->clientMock->placeRequest($transferObject)
        );
        try {
            $this->clientMock->placeRequest($transferObject);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->assertEquals("Unable to retrieve payment information", $e->getMessage());
        }
    }

    /**
     * @return array
     */
    public function placeRequestDataProvider()
    {
        return [
            'success' => [
                'expectedRequest' => [],
                'expectedResponse' => [$this->buildExpectedResult()]
            ]
        ];
    }

    private function buildExpectedResult()
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <!DOCTYPE Report SYSTEM \"https://ebctest.cybersource.com/ebctest/reports/dtd/tdr_1_5.dtd\">
            
            <Report xmlns=\"https://ebctest.cybersource.com/ebctest/reports/dtd/tdr_1_5.dtd\" Name=\"Transaction Detail\" Version=\"1.5\" MerchantID=\"chtest\" ReportStartDate=\"2017-02-23 15:51:07.460+00:00\" ReportEndDate=\"2017-02-23 15:51:07.460+00:00\">
              <Requests>
                <Request MerchantReferenceNumber=\"000000123\" RequestDate=\"2017-02-01T00:28:59+00:00\" RequestID=\"4859089398406650701013\" SubscriptionID=\"\" Source=\"SOAP Toolkit API\" TransactionReferenceNumber=\"02XFZ3GGIYG4S5PL\">
                  <BillTo>
                    <FirstName>FirstName</FirstName>
                    <LastName>LastName</LastName>
                    <Address1>2741 Jade St</Address1>
                    <City>Vancouver</City>
                    <State>BC</State>
                    <Zip>V7V 1Y8</Zip>
                    <Email>test@collinsharper.com</Email>
                    <Country>CA</Country>
                    <Phone>1231231231</Phone>
                  </BillTo>
                  <ShipTo>
                    <Phone>1231231231</Phone>
                  </ShipTo>
                  <PaymentMethod>
                    <Check>
                      <AccountSuffix>4100</AccountSuffix>
                      <CheckNumber />
                    </Check>
                  </PaymentMethod>
                  <LineItems>
                    <LineItem Number=\"0\">
                      <FulfillmentType />
                      <Quantity>1</Quantity>
                      <UnitPrice>37.00</UnitPrice>
                      <TaxAmount>0.00</TaxAmount>
                      <ProductCode>default</ProductCode>
                    </LineItem>
                  </LineItems>
                  <ApplicationReplies>
                    <ApplicationReply Name=\"ics_ecp_debit\">
                      <RCode>1</RCode>
                      <RFlag>SOK</RFlag>
                      <RMsg>Request was processed successfully.</RMsg>
                    </ApplicationReply>
                  </ApplicationReplies>
                  <PaymentData>
                    <PaymentRequestID>4859089398406650701013</PaymentRequestID>
                    <PaymentProcessor>cardinal</PaymentProcessor>
                    <Amount>37.00</Amount>
                    <CurrencyCode>USD</CurrencyCode>
                    <TotalTaxAmount>0.00</TotalTaxAmount>
                    <EventType>TRANSMITTED</EventType>
                  </PaymentData>
                </Request>
              </Requests>
            </Report>

        ";
    }
}
