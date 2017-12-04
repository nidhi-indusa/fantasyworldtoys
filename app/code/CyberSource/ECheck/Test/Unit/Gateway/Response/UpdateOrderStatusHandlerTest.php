<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Response;

use Magento\Sales\Model\Order;
use CyberSource\ECheck\Gateway\Response\UpdateOrderStatusHandler;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderRepository;

class UpdateOrderStatusHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testHandle()
    {
        $orderPayment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock = $this->getMock(OrderInterface::class);

        $orderMock->expects(static::once())
            ->method('setState')
            ->with(Order::STATE_PROCESSING);

        $orderMock->expects(static::once())
            ->method('setStatus')
            ->with(Order::STATE_PROCESSING);

        $orderPayment->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $orderPayment->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(false);

        $orderPayment->expects(static::once())
            ->method('setIsTransactionPending')
            ->with(false);

        $repositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = new UpdateOrderStatusHandler($repositoryMock);
        $request->handle(['payment' => $orderPayment], []);
        try {
            $request->handle([], []);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('OrderPaymentInterface should be provided', $e->getMessage());
        }
    }
}
