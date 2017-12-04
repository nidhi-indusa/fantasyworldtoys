<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Http\Client;

use CyberSource\Core\Service\AbstractConnection;
use CyberSource\ECheck\Gateway\Http\Client\Client;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Psr\Log\LoggerInterface;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    const TXN_ID = 'fcd7f001e9274fdefb14bff91c799306';

    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $clientMock;

    public function setUp()
    {
        $scopeInterface = $this->getMock(ScopeConfigInterface::class);

        $scopeInterface->expects($this->at(0))
            ->method('getValue')
            ->with(AbstractConnection::IS_TEST_MODE_CONFIG_PATH)
            ->willReturn(1);

        $scopeInterface->expects($this->at(1))
            ->method('getValue')
            ->with(AbstractConnection::TEST_WSDL_PATH)
            ->willReturn(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'service.wsdl');

        $scopeInterface->expects($this->at(2))
            ->method('getValue')
            ->with(AbstractConnection::MERCHANT_ID_PATH)
            ->willReturn('chtest');

        $scopeInterface->expects($this->at(3))
            ->method('getValue')
            ->with(AbstractConnection::TRANSACTION_KEY_PATH)
            ->willReturn('12341234');

        $logger = $this->getMock(LoggerInterface::class);

        $soapClientMock = $this->getMockFromWsdl(
            'https://ics2wstesta.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.134.wsdl',
            'CyberSourceTransactionWS'
        );

        $this->clientMock = new Client($scopeInterface, $logger, $soapClientMock);
        $soapStub = $this->getMockFromWsdl(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'service.wsdl', 'MyMockClass');
        $soapStub->expects(static::any())
            ->method('runTransaction')
            ->willReturn($this->buildRunTransactionResponse());

        $this->clientMock->setSoapClient($soapStub);
    }

    /**
     * @param array $expectedRequest
     * @param array $expectedResponse
     *
     * @dataProvider placeRequestDataProvider
     */
    public function testPlaceRequest(array $expectedRequest, array $expectedResponse)
    {
        /** @var TransferInterface|\PHPUnit_Framework_MockObject_MockObject $transferObject */
        $transferObject = $this->getMock(TransferInterface::class);
        $transferObject->expects(static::any())
            ->method('getBody')
            ->willReturn($expectedRequest);

        static::assertEquals(
            $expectedResponse,
            $this->clientMock->placeRequest($transferObject)
        );
    }

    /**
     * @return array
     */
    public function placeRequestDataProvider()
    {
        return [
            'success' => [
                'expectedRequest' => [
                    'merchantID' => 'chtest',
                    'merchantReferenceCode' => '000000135',
                    'ecDebitService' => (object) ['run' => 'true'],
                    'billTo' => (object)
                    [
                        'firstName' => 'FirstName',
                        'lastName' => 'LastName',
                        'street1' => '2741 Jade St',
                        'city' => 'Vancouver',
                        'state' => 'BC',
                        'postalCode' => 'V7V 1Y8',
                        'country' => 'CA',
                        'phoneNumber' => '1231231231',
                        'email' => 'test@collinsharper.com',
                    ],
                    'purchaseTotals' => (object)
                    [
                        'currency' => 'USD',
                        'grandTotalAmount' => '41.00',
                    ],
                    'check' => (object)
                    [
                        'accountNumber' => '4100',
                        'accountType' => 'C',
                        'bankTransitNumber' => '071923284',
                        'secCode' => 'WEB',
                    ],
                    'item_0_unitPrice' => 36
                ],
                'expectedResponse' => $this->buildRunTransactionResponse()
            ]
        ];
    }

    private function buildRunTransactionResponse()
    {
        return [
            'merchantReferenceCode' => '000000138',
            'requestID' => '4878562707066010004010',
            'decision' => 'ACCEPT',
            'reasonCode' => 100,
            'requestToken' => 'Ahjr7wSTCHeodlfxmOoqOEuOIlA4pLjiJQOK0gdy2Nx0MmkmW6QHchdACyYQ71Dsr+Mx1FQA7hxD',
            'purchaseTotals' => (object)
                [
                    'currency' => 'USD',
                ],
            'ecDebitReply' => (object)
                [
                    'reasonCode' => 100,
                    'settlementMethod' => 'A',
                    'requestDateTime' => '2017-02-23T13:24:30Z',
                    'amount' => '147.00',
                    'verificationLevel' => 1,
                    'processorTransactionID' => 'ABCDEFGHIJ1234567890123456789012345678901234567890',
                    'reconciliationID' => '02XFZ3HGIZ6I64G8',
                    'processorResponse' => 'OK',
                ]
        ];
    }
}
