<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Request;

use CyberSource\ECheck\Gateway\Config\Config;
use CyberSource\ECheck\Gateway\Request\RefundRequest;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use CyberSource\ECheck\Gateway\Request\VoidRequest;

class RefundRequestTest extends \PHPUnit_Framework_TestCase
{
    
    private $counter = 0;
    
    public function testBuild()
    {
        $txnId = 'fcd7f001e9274fdefb14bff91c799306';
        $merchantId = 'chtest';
        $invoiceId = '000000135';

        $expectation = [
            'merchantID' => 'chtest',
            'merchantReferenceCode' => '000000135',
            'voidService' => (object) [
                'run' => "true",
                'voidRequestID' => $txnId
            ],
            'partnerSolutionID' => 'T54H9OLO'
        ];

        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects(static::once())
            ->method('getMerchantId')
            ->willReturn($merchantId);

        $orderMock = $this->getMock(OrderAdapterInterface::class);

        $orderMock->expects(static::once())
            ->method('getOrderIncrementId')
            ->willReturn($invoiceId);

        $payment = $this->getMock(PaymentDataObjectInterface::class);
        $this->orderPayment = $this->getMock(OrderPaymentInterface::class);

        $this->orderPayment->expects(static::any())
            ->method('getLastTransId')
            ->willReturn($txnId);

        $payment->expects(static::any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $payment->expects(static::any())
            ->method('getPayment')
            ->will($this->returnCallback(function () {
                $this->counter++;
                return ($this->counter == 2) ? null : $this->orderPayment;
            }));

        $remoteAddressMock = $this->getMockBuilder(\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ConfigInterface $configMock */
        $request = new RefundRequest($configMock, $remoteAddressMock);

        static::assertEquals(
            $expectation,
            $request->build(['payment' => $payment])
        );
        
        try {
            static::assertEquals(
                $expectation,
                $request->build([])
            );
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Payment data object should be provided', $e->getMessage());
        }
        
        try {
            $request->build(['payment' => $payment]);
        } catch (\LogicException $e) {
            $this->assertEquals('Order payment should be provided.', $e->getMessage());
        }
    }
}
