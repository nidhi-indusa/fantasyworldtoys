<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Response;

use Magento\Sales\Model\Order;
use CyberSource\ECheck\Gateway\Response\VoidResponseHandler;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderRepository;

class VoidResponseHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testHandle()
    {
        $paymentDO = $this->getMock(PaymentDataObjectInterface::class);
        $orderMock = $this->getMock(OrderInterface::class);

        $orderMock->expects(static::once())
            ->method('setState')
            ->with(Order::STATE_CLOSED);

        $orderMock->expects(static::once())
            ->method('setStatus')
            ->with(Order::STATE_CLOSED);

        $paymentModel = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentModel->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentModel);

        $paymentModel->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(true);

        $paymentModel->expects(static::once())
            ->method('setShouldCloseParentTransaction')
            ->with(true);

        $paymentModel->expects(static::once())
            ->method('setIsTransactionPending')
            ->with(false);

        $repositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = new VoidResponseHandler($repositoryMock);
        $request->handle(['payment' => $paymentDO], []);
        try {
            $request->handle([], []);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Payment data object should be provided', $e->getMessage());
        }
    }
}
