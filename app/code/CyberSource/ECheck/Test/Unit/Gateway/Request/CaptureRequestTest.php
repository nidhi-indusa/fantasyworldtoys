<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Request;

use CyberSource\ECheck\Gateway\Config\Config;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use CyberSource\ECheck\Gateway\Request\CaptureRequest;

class CaptureRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $txnId = 'fcd7f001e9274fdefb14bff91c799306';
        $merchantId = 'chtest';

        $expectation = [
            'merchantID' => 'chtest',
            'type' => 'transaction',
            'subtype' => 'transactionDetail',
            'versionNumber' => '1.5',
            'requestID' => 'fcd7f001e9274fdefb14bff91c799306',
        ];

        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->method('getMerchantId')->willReturn($merchantId);

        $remoteAddressMock = $this->getMockBuilder(\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentModel = $this->getMock(OrderPaymentInterface::class);

        $paymentModel->expects(static::once())
            ->method('getLastTransId')
            ->willReturn($txnId);

        /** @var ConfigInterface $configMock */
        $request = new CaptureRequest($configMock, $remoteAddressMock);

        static::assertEquals(
            $expectation,
            $request->build(['payment' => $paymentModel])
        );
        
        try {
            static::assertEquals(
                $expectation,
                $request->build([])
            );
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('OrderPaymentInterface should be provided', $e->getMessage());
        }
    }
}
