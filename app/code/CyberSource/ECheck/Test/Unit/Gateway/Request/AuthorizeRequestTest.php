<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use CyberSource\ECheck\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use CyberSource\ECheck\Gateway\Request\AuthorizationRequest;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class AuthorizeRequestTest extends \PHPUnit_Framework_TestCase
{
    
    private $counter = 0;
    
    public function testBuild()
    {
        $invoiceId = '000000135';
        $grandTotal = 41.0;
        $currencyCode = 'USD';
        $storeId = 1;
        $merchantId = 'chtest';

        $expectation =  [
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
                    'ipAddress' => null
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
            'item_0_unitPrice' => 36,
            'partnerSolutionID' => 'T54H9OLO'
        ];

        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $remoteAddressMock = $this->getMockBuilder(\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects(static::once())
            ->method('getMerchantId')
            ->willReturn($merchantId);

        $orderItemMock = $this->getMock(OrderItemInterface::class);
        $orderItemMock->expects(static::any())
            ->method('getPrice')
            ->willReturn(36);

        $orderMock = $this->getMock(OrderAdapterInterface::class);

        $orderMock->expects(static::any())
            ->method('getItems')
            ->willReturn([$orderItemMock]);

        $addressMock = $this->buildAddressMock();
        $payment = $this->getMock(PaymentDataObjectInterface::class);
        $this->orderPayment = $this->getMock(OrderPaymentInterface::class);

        $this->orderPayment->expects($this->at(0))
            ->method('getAdditionalInformation')
            ->with(
                'check_bank_transit_number'
            )
            ->willReturn('071923284');

        $this->orderPayment->expects($this->at(1))
            ->method('getAdditionalInformation')
            ->with(
                'check_account_number'
            )
            ->willReturn('4100');


        $payment->expects(static::any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $payment->expects(static::any())
            ->method('getPayment')
            ->will($this->returnCallback(function () {
                $this->counter++;
                return ($this->counter == 2) ? null : $this->orderPayment;
            }));

        $orderMock->expects(static::any())
            ->method('getBillingAddress')
            ->willReturn($addressMock);

        $orderMock->expects(static::once())
            ->method('getOrderIncrementId')
            ->willReturn($invoiceId);
        $orderMock->expects(static::once())
            ->method('getGrandTotalAmount')
            ->willReturn($grandTotal);
        $orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn($currencyCode);
        $orderMock->expects(static::any())
            ->method('getStoreId')
            ->willReturn($storeId);

        /** @var ConfigInterface $configMock */
        $request = new AuthorizationRequest($configMock, $remoteAddressMock);

        static::assertEquals(
            $expectation,
            $request->build(['payment' => $payment])
        );
        
        try {
            $request->build([]);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Payment data object should be provided', $e->getMessage());
        }
        
        try {
            $request->build(['payment' => $payment]);
        } catch (\LogicException $e) {
            $this->assertEquals('Order payment should be provided.', $e->getMessage());
        }
    }

    private function buildAddressMock()
    {
        $firstName = 'FirstName';
        $lastName = 'LastName';
        $street1 = '2741 Jade St';
        $city = 'Vancouver';
        $state = 'BC';
        $postalCode = 'V7V 1Y8';
        $country = 'CA';
        $phoneNumber = '1231231231';
        $email = 'test@collinsharper.com';

        $addressMock = $this->getMock(AddressAdapterInterface::class);

        $addressMock->expects(static::any())
            ->method('getFirstname')
            ->willReturn($firstName);
        $addressMock->expects(static::any())
            ->method('getLastname')
            ->willReturn($lastName);
        $addressMock->expects(static::any())
            ->method('getStreetLine1')
            ->willReturn($street1);
        $addressMock->expects(static::any())
            ->method('getCity')
            ->willReturn($city);
        $addressMock->expects(static::any())
            ->method('getRegionCode')
            ->willReturn($state);
        $addressMock->expects(static::any())
            ->method('getPostcode')
            ->willReturn($postalCode);
        $addressMock->expects(static::any())
            ->method('getCountryId')
            ->willReturn($country);
        $addressMock->expects(static::any())
            ->method('getTelephone')
            ->willReturn($phoneNumber);
        $addressMock->expects(static::any())
            ->method('getEmail')
            ->willReturn($email);

        return $addressMock;
    }
}
