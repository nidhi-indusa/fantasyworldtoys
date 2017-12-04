<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Test\Unit\Model\Order;

class CreditmemoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Sales\Model\OrderFactory |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderFactory;

    /**
     * @var \CyberSource\Core\Model\Order\Creditmemo
     */
    protected $creditmemo;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cmItemCollectionFactoryMock;

    public function setUp()
    {
        $this->orderFactory = $this->getMock(
            '\Magento\Sales\Model\OrderFactory',
            ['create'],
            [],
            '',
            false
        );

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cmItemCollectionFactoryMock = $this->getMockBuilder(
            '\Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\CollectionFactory'
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $arguments = [
            'context' => $this->getMock('Magento\Framework\Model\Context', [], [], '', false),
            'registry' => $this->getMock('Magento\Framework\Registry', [], [], '', false),
            'localeDate' => $this->getMock('Magento\Framework\Stdlib\DateTime\TimezoneInterface', [], [], '', false),
            'dateTime' => $this->getMock('Magento\Framework\Stdlib\DateTime', [], [], '', false),
            'creditmemoConfig' => $this->getMock('Magento\Sales\Model\Order\Creditmemo\Config', [], [], '', false),
            'orderFactory' => $this->orderFactory,
            'cmItemCollectionFactory' => $this->cmItemCollectionFactoryMock,
            'calculatorFactory' => $this->getMock('Magento\Framework\Math\CalculatorFactory', [], [], '', false),
            'storeManager' => $this->getMock('Magento\Store\Model\StoreManagerInterface', [], [], '', false),
            'commentFactory' => $this->getMock(
                'Magento\Sales\Model\Order\Creditmemo\CommentFactory',
                [],
                [],
                '',
                false
            ),
            'commentCollectionFactory' => $this->getMock(
                'Magento\Sales\Model\ResourceModel\Order\Creditmemo\Comment\CollectionFactory',
                [],
                [],
                '',
                false
            ),
        ];
        $this->creditmemo = $objectManagerHelper->getObject(
            '\CyberSource\Core\Model\Order\Creditmemo',
            $arguments
        );
    }

    public function testCanRefund()
    {
        $orderId = 100000041;
        $this->creditmemo->setOrderId($orderId);
        $entityName = 'creditmemo';

        $payment = $this->getMock(
            'Magento\Sales\Model\Order\Payment',
            [],
            [],
            '',
            false
        );

        $payment->expects($this->atLeastOnce())
            ->method('canRefund')
            ->willReturn(true);

        $order = $this->getMock(
            'Magento\Sales\Model\Order',
            ['load', 'setHistoryEntityName', '__wakeUp', 'getPayment', 'getStatus'],
            [],
            '',
            false
        );

        $order->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($payment);

        $this->creditmemo->setOrderId($orderId);

        $order->expects($this->atLeastOnce())
            ->method('setHistoryEntityName')
            ->with($entityName)
            ->will($this->returnSelf());
        $order->expects($this->atLeastOnce())
            ->method('load')
            ->with($orderId)
            ->will($this->returnValue($order));

        $order->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn('new');

        $this->orderFactory->expects($this->atLeastOnce())
            ->method('create')
            ->will($this->returnValue($order));

        $this->creditmemo->setState('new');

        $canRefund = $this->creditmemo->canRefund();

        $this->assertTrue($canRefund);
    }

    public function testCanRefundFalse()
    {
        $this->creditmemo->setState(3);
        $canRefund = $this->creditmemo->canRefund();
        $this->assertFalse($canRefund);
    }
}
