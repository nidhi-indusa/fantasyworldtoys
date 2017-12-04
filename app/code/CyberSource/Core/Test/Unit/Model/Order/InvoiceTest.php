<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Test\Unit\Model\Order;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\OrderFactory;

class InvoiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \CyberSource\Core\Model\Order\Invoice
     */
    protected $model;

    /**
     * @var string
     */
    protected $entityType = 'invoice';

    /**
     * @var OrderFactory |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Model\Order
     */
    protected $orderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Model\Order\Payment
     */
    protected $paymentMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\ManagerInterface
     */
    protected $eventManagerMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $helperManager;

    protected function setUp()
    {
        $magentoObjectManagerFactory = \Magento\Framework\App\Bootstrap::createObjectManagerFactory(BP, $_SERVER);
        $objectManager = $magentoObjectManagerFactory->create($_SERVER);
        \Magento\Framework\App\ObjectManager::setInstance($objectManager);

        $this->helperManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->orderMock = $this->getMockBuilder(
            'Magento\Sales\Model\Order'
        )->disableOriginalConstructor()->setMethods(
            [
                'getPayment', '__wakeup', 'load', 'setHistoryEntityName', 'getStore', 'getBillingAddress',
                'getShippingAddress', 'getConfig', 'getStateDefaultStatus'
            ]
        )->getMock();
        $this->orderMock->expects($this->any())
            ->method('setHistoryEntityName')
            ->with($this->entityType)
            ->will($this->returnSelf());


        $this->paymentMock = $this->getMockBuilder(
            'Magento\Sales\Model\Order\Payment'
        )->disableOriginalConstructor()->setMethods(
            ['canVoid', '__wakeup', 'canCapture', 'capture', 'pay', 'getTransactionId', 'cancelInvoice']
        )->getMock();

        $this->orderFactory = $this->getMock('Magento\Sales\Model\OrderFactory', ['create'], [], '', false);

        $this->eventManagerMock = $this->getMock('\Magento\Framework\Event\ManagerInterface', [], [], '', false);
        $contextMock = $this->getMock('\Magento\Framework\Model\Context', [], [], '', false);
        $contextMock->expects($this->any())
            ->method('getEventDispatcher')
            ->willReturn($this->eventManagerMock);

        $arguments = [
            'context' => $contextMock,
            'orderFactory' => $this->orderFactory,
            'orderResourceFactory' => $this->getMock(
                'Magento\Sales\Model\ResourceModel\OrderFactory',
                [],
                [],
                '',
                false
            ),
            'calculatorFactory' => $this->getMock(
                'Magento\Framework\Math\CalculatorFactory',
                [],
                [],
                '',
                false
            ),
            'invoiceItemCollectionFactory' => $this->getMock(
                'Magento\Sales\Model\ResourceModel\Order\Invoice\Item\CollectionFactory',
                [],
                [],
                '',
                false
            ),
            'invoiceCommentFactory' => $this->getMock(
                'Magento\Sales\Model\Order\Invoice\CommentFactory',
                [],
                [],
                '',
                false
            ),
            'commentCollectionFactory' => $this->getMock(
                'Magento\Sales\Model\ResourceModel\Order\Invoice\Comment\CollectionFactory',
                [],
                [],
                '',
                false
            ),
        ];
        $this->model = $this->helperManager->getObject('\CyberSource\Core\Model\Order\Invoice', $arguments);
        $this->model->setOrder($this->orderMock);
    }

    /**
     * @dataProvider canRefundDataProvider
     * @param string $state
     * @param float $baseGrandTotal
     * @param float $baseTotalRefunded
     * @param bool $expectedResult
     */
    public function testCanRefund($state, $baseGrandTotal, $baseTotalRefunded, $status, $expectedResult)
    {
        $this->model->setState($state);
        $this->model->setBaseGrandTotal($baseGrandTotal);
        $this->model->setBaseTotalRefunded($baseTotalRefunded);
        $this->model->getOrder()->setStatus($status);
        $this->assertEquals($expectedResult, $this->model->canRefund());
    }

    /**
     * Data provider for testCanRefund
     *
     * @return array
     */
    public function canRefundDataProvider()
    {
        return [
            [Invoice::STATE_OPEN, 0.00, 0.00, 'payment_review', false],
            [Invoice::STATE_CANCELED, 1.00, 0.01, 'payment_review', false],
            [Invoice::STATE_PAID, 1.00, 0.00, 'success', true],
            [Invoice::STATE_PAID, 1.000101, 1.0000, 'success', true],
            [Invoice::STATE_PAID, 1.0001, 1.00, 'payment_review', false],
            [Invoice::STATE_PAID, 1.00, 1.0001, 'payment_review', false],
            [Invoice::STATE_PAID, 1.00, 1.0001, 'payment_review', false],
        ];
    }

    public function testCaptureNotPaid()
    {
        $this->model->setIsPaid(false);
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->expects($this->once())->method('capture')->with($this->model)->willReturnSelf();
        $this->paymentMock->expects($this->once())->method('getTransactionId')->willReturn("1");
        $this->paymentMock->expects($this->never())->method('pay');
        $this->paymentMock->expects($this->once())->method('cancelInvoice')->with($this->model)->willReturnSelf();

        $item = $this->getMock(
            '\Magento\Sales\Model\ResourceModel\Order\Invoice\Item',
            ['isDeleted', 'cancel'],
            [],
            '',
            false
        );

        $item->expects($this->once())->method('isDeleted')->willReturn(false);
        $item->expects($this->once())->method('cancel')->willReturnSelf();

        $this->orderMock->expects($this->any())->method('setState')->with('processing')->willReturnSelf();
        $this->orderMock->expects($this->once())->method('getConfig')->willReturnSelf();
        $this->orderMock->expects($this->once())->method('getStateDefaultStatus')->with('processing')->willReturn('processing');

        $data = [
            InvoiceInterface::ITEMS => [$item]
        ];

        $this->model->setData($data);
        $this->eventManagerMock
            ->expects($this->once())
            ->method('dispatch')
            ->with('sales_order_invoice_cancel');
        $this->assertEquals($this->model, $this->model->capture());
    }

    public function testCapturePaid()
    {
        $this->model->setIsPaid(true);
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->expects($this->any())->method('capture')->with($this->model)->willReturnSelf();
        $this->paymentMock->expects($this->once())->method('getTransactionId')->willReturn("1");
        $this->mockPay('sales_order_invoice_pay');
        $this->assertEquals($this->model, $this->model->capture());
    }

    public function mockPay($event)
    {
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->expects($this->once())->method('pay')->with($this->model)->willReturnSelf();
        $this->eventManagerMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($event);
    }

    public function testRegisterExistingInvoice()
    {
        $this->model->setId(1);

        $this->setExpectedException(
            '\Magento\Framework\Exception\LocalizedException',
            'We cannot register an existing invoice'
        );

        $this->model->register();
    }

    public function testRegister()
    {
        $item = $this->getMock(
            '\Magento\Sales\Model\ResourceModel\Order\Invoice\Item',
            ['getQty', 'isDeleted'],
            [],
            '',
            false
        );

        $item->expects($this->once())->method('getQty')->willReturn(0);
        $item->expects($this->never())->method('register');

        $data = [
            InvoiceInterface::ITEMS => [$item]
        ];

        $this->model->setData($data);

        $this->paymentMock->expects($this->once())->method('canCapture')->willReturn(true);
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->paymentMock);

        $this->eventManagerMock
            ->expects($this->once())
            ->method('dispatch')
            ->with('sales_order_invoice_register');
        $this->assertEquals($this->model, $this->model->register());
    }
}
