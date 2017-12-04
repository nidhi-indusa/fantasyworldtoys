<?php

namespace CyberSource\Core\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory as HistoryCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item;

/**
 * Test class for \Magento\Sales\Model\Order
 */
class OrderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentCollectionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderItemCollectionFactoryMock;

    /**
     * @var \CyberSource\Core\Model\Order
     */
    protected $order;

    /**
     * @var \Magento\Framework\Event\Manager | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManager;

    /**
     * @var string
     */
    protected $incrementId;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $item;

    /**
     * @var HistoryCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $historyCollectionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $salesOrderCollectionFactoryMock;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $salesOrderCollectionMock;

    /**
     * @var ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepository;

    protected function setUp()
    {
        Bootstrap::create(BP, $_SERVER)->createApplication(Http::class);

        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->paymentCollectionFactoryMock = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->orderItemCollectionFactoryMock = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->historyCollectionFactoryMock = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->salesOrderCollectionFactoryMock = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\CollectionFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->item = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\Item',
            ['isDeleted', 'getQtyToInvoice', 'getParentItemId', 'getQuoteItemId', 'getLockedDoInvoice'],
            [],
            '',
            false
        );
        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isDeleted',
                'getQtyToInvoice',
                'getParentItemId',
                'getQuoteItemId',
                'getLockedDoInvoice',
                'getProductId'
            ])
            ->getMock();
        $this->salesOrderCollectionMock = $this->getMockBuilder('Magento\Sales\Model\ResourceModel\Order\Collection')
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'load', 'getFirstItem'])
            ->getMock();
        $collection = $this->getMock('Magento\Sales\Model\ResourceModel\Order\Item\Collection', [], [], '', false);
        $collection->expects($this->any())
            ->method('setOrderFilter')
            ->willReturnSelf();
        $collection->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->item]);
        $collection->expects(self::any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->item]));
        $this->orderItemCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($collection);

        $this->priceCurrency = $this->getMockForAbstractClass(
            'Magento\Framework\Pricing\PriceCurrencyInterface',
            [],
            '',
            false,
            false,
            true,
            ['round']
        );

        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->incrementId = '#00000001';
        $this->eventManager = $this->getMock('Magento\Framework\Event\Manager', [], [], '', false);
        $context = $this->getMock('Magento\Framework\Model\Context', ['getEventDispatcher'], [], '', false);
        $context->expects($this->any())
            ->method('getEventDispatcher')
            ->willReturn($this->eventManager);

        $this->order = $helper->getObject(
            'CyberSource\Core\Model\Order',
            [
                'paymentCollectionFactory' => $this->paymentCollectionFactoryMock,
                'orderItemCollectionFactory' => $this->orderItemCollectionFactoryMock,
                'data' => ['increment_id' => $this->incrementId],
                'context' => $context,
                'historyCollectionFactory' => $this->historyCollectionFactoryMock,
                'salesOrderCollectionFactory' => $this->salesOrderCollectionFactoryMock,
                'priceCurrency' => $this->priceCurrency,
                'productRepository' => $this->productRepository
            ]
        );
    }


    public function testCanCancelCanUnhold()
    {
        $this->order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD, true);
        $this->order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
        $this->assertFalse($this->order->canCancel());
    }

    public function testCanCancelIsPaymentReview()
    {
        $this->order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD, false);
        $this->order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
        $this->assertFalse($this->order->canCancel());
    }

    public function testCanCancelIsComplete()
    {
        $this->order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD, false);
        $this->order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
        $this->assertFalse($this->order->canCancel());
    }

    public function testCanCancelIsFraud()
    {
        $this->order->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD);
        $this->assertFalse($this->order->canCancel());
    }

    public function testCanInvoice()
    {
        $this->item->expects($this->any())
            ->method('getQtyToInvoice')
            ->willReturn(42);
        $this->item->expects($this->any())
            ->method('getLockedDoInvoice')
            ->willReturn(false);

        $this->assertTrue($this->order->canInvoice());
    }

    public function testCanInvoiceIsFraud()
    {
        $this->order->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD);
        $this->assertFalse($this->order->canInvoice());
    }

    /**
     * @param string $status
     *
     * @dataProvider notInvoicingStatesProvider
     */
    public function testCanNotInvoiceInSomeStates($status)
    {
        $this->item->expects($this->any())
            ->method('getQtyToInvoice')
            ->willReturn(42);
        $this->item->expects($this->any())
            ->method('getLockedDoInvoice')
            ->willReturn(false);
        $this->order->setData('state', $status);
        $this->assertFalse($this->order->canInvoice());
    }

    public function testCanNotInvoiceWhenActionInvoiceFlagIsFalse()
    {
        $this->item->expects($this->any())
            ->method('getQtyToInvoice')
            ->willReturn(42);
        $this->item->expects($this->any())
            ->method('getLockedDoInvoice')
            ->willReturn(false);
        $this->order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_INVOICE, false);
        $this->assertFalse($this->order->canInvoice());
    }

    public function testCanNotInvoiceWhenLockedInvoice()
    {
        $this->item->expects($this->any())
            ->method('getQtyToInvoice')
            ->willReturn(42);
        $this->item->expects($this->any())
            ->method('getLockedDoInvoice')
            ->willReturn(true);
        $this->assertFalse($this->order->canInvoice());
    }

    public function testCanNotInvoiceWhenDidNotHaveQtyToInvoice()
    {
        $this->item->expects($this->any())
            ->method('getQtyToInvoice')
            ->willReturn(0);
        $this->item->expects($this->any())
            ->method('getLockedDoInvoice')
            ->willReturn(false);
        $this->assertFalse($this->order->canInvoice());
    }

    public function testCanCancelCanReviewPayment()
    {
        $paymentMock = $this->getMockBuilder('Magento\Sales\Model\ResourceModel\Order\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['isDeleted', 'canReviewPayment', 'canFetchTransactionInfo', '__wakeUp'])
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('canReviewPayment')
            ->will($this->returnValue(false));
        $paymentMock->expects($this->any())
            ->method('canFetchTransactionInfo')
            ->will($this->returnValue(true));
        $this->preparePaymentMock($paymentMock);
        $this->order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD, false);
        $this->order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
        $this->assertFalse($this->order->canCancel());
    }

    public function testCanCancelAllInvoiced()
    {
        $paymentMock = $this->getMockBuilder('Magento\Sales\Model\ResourceModel\Order\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['isDeleted', 'canReviewPayment', 'canFetchTransactionInfo', '__wakeUp'])
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('canReviewPayment')
            ->will($this->returnValue(false));
        $paymentMock->expects($this->any())
            ->method('canFetchTransactionInfo')
            ->will($this->returnValue(false));
        $collectionMock = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\Item\Collection',
            ['getItems', 'setOrderFilter'],
            [],
            '',
            false
        );
        $this->orderItemCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($collectionMock));
        $collectionMock->expects($this->any())
            ->method('setOrderFilter')
            ->willReturnSelf();
        $this->preparePaymentMock($paymentMock);

        $this->prepareItemMock(0);

        $this->order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD, false);
        $this->order->setState(\Magento\Sales\Model\Order::STATE_NEW);

        $this->item->expects($this->any())
            ->method('isDeleted')
            ->willReturn(false);
        $this->item->expects($this->any())
            ->method('getQtyToInvoice')
            ->willReturn(0);

        $this->assertFalse($this->order->canCancel());
    }

    public function testCanCancelState()
    {
        $paymentMock = $this->getMockBuilder('Magento\Sales\Model\ResourceModel\Order\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['isDeleted', 'canReviewPayment', 'canFetchTransactionInfo', '__wakeUp'])
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('canReviewPayment')
            ->will($this->returnValue(false));
        $paymentMock->expects($this->any())
            ->method('canFetchTransactionInfo')
            ->will($this->returnValue(false));

        $this->preparePaymentMock($paymentMock);

        $this->prepareItemMock(1);
        $this->order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD, false);
        $this->order->setState(\Magento\Sales\Model\Order::STATUS_FRAUD);
        $this->assertFalse($this->order->canCancel());
    }

    /**
     * @param bool $cancelActionFlag
     * @dataProvider dataProviderActionFlag
     */
    public function testCanCancelActionFlag($cancelActionFlag)
    {
        $paymentMock = $this->getMockBuilder('Magento\Sales\Model\ResourceModel\Order\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['isDeleted', 'canReviewPayment', 'canFetchTransactionInfo', '__wakeUp'])
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('canReviewPayment')
            ->will($this->returnValue(false));
        $paymentMock->expects($this->any())
            ->method('canFetchTransactionInfo')
            ->will($this->returnValue(false));

        $this->preparePaymentMock($paymentMock);

        $this->prepareItemMock(1);

        $actionFlags = [
            \Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD => false,
            \Magento\Sales\Model\Order::ACTION_FLAG_CANCEL => $cancelActionFlag,
        ];
        foreach ($actionFlags as $action => $flag) {
            $this->order->setActionFlag($action, $flag);
        }
        $this->order->setData('state', \Magento\Sales\Model\Order::STATE_NEW);

        $this->item->expects($this->any())
            ->method('isDeleted')
            ->willReturn(false);
        $this->item->expects($this->any())
            ->method('getQtyToInvoice')
            ->willReturn(42);

        $this->assertEquals($cancelActionFlag, $this->order->canCancel());
    }


    /**
     * @param $paymentMock
     */
    protected function preparePaymentMock($paymentMock)
    {
        $iterator = new \ArrayIterator([$paymentMock]);

        $collectionMock = $this->getMockBuilder('Magento\Sales\Model\ResourceModel\Order\Payment\Collection')
            ->disableOriginalConstructor()
            ->setMethods(['setOrderFilter', 'getIterator'])
            ->getMock();
        $collectionMock->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue($iterator));
        $collectionMock->expects($this->any())
            ->method('setOrderFilter')
            ->will($this->returnSelf());

        $this->paymentCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($collectionMock));
    }

    /**
     * Prepare payment for the order
     *
     * @param \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject $order
     * @param array $mockedMethods
     * @return \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function _prepareOrderPayment($order, $mockedMethods = [])
    {
        $payment = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')->disableOriginalConstructor()->getMock();
        foreach ($mockedMethods as $method => $value) {
            $payment->expects($this->any())->method($method)->will($this->returnValue($value));
        }
        $payment->expects($this->any())->method('isDeleted')->will($this->returnValue(false));

        $order->setData(\Magento\Sales\Api\Data\OrderInterface::PAYMENT, $payment);

        return $payment;
    }

    /**
     * Get action flags
     *
     */
    protected function _getActionFlagsValues()
    {
        return [
            [],
            [
                \Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD => false,
                \Magento\Sales\Model\Order::ACTION_FLAG_CANCEL => false
            ],
            [
                \Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD => false,
                \Magento\Sales\Model\Order::ACTION_FLAG_CANCEL => true
            ]
        ];
    }

    /**
     * Get order statuses
     *
     * @return array
     */
    protected function _getOrderStatuses()
    {
        return [
            \Magento\Sales\Model\Order::STATE_HOLDED,
            \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW,
            \Magento\Sales\Model\Order::STATE_CANCELED,
            \Magento\Sales\Model\Order::STATE_COMPLETE,
            \Magento\Sales\Model\Order::STATE_CLOSED,
            \Magento\Sales\Model\Order::STATE_PROCESSING
        ];
    }

    /**
     * @param int $qtyInvoiced
     * @return void
     */
    protected function prepareItemMock($qtyInvoiced)
    {
        $itemMock = $this->getMockBuilder('Magento\Sales\Model\ResourceModel\Order\Item')
            ->disableOriginalConstructor()
            ->setMethods(['isDeleted', 'filterByTypes', 'filterByParent', 'getQtyToInvoice', '__wakeUp'])
            ->getMock();

        $itemMock->expects($this->any())
            ->method('getQtyToInvoice')
            ->will($this->returnValue($qtyInvoiced));

        $iterator = new \ArrayIterator([$itemMock]);

        $itemCollectionMock = $this->getMockBuilder('Magento\Sales\Model\ResourceModel\Order\Item\Collection')
            ->disableOriginalConstructor()
            ->setMethods(['setOrderFilter', 'getIterator', 'getItems'])
            ->getMock();
        $itemCollectionMock->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue($iterator));
        $itemCollectionMock->expects($this->any())
            ->method('setOrderFilter')
            ->will($this->returnSelf());

        $this->orderItemCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($itemCollectionMock));
    }

    public function canVoidPaymentDataProvider()
    {
        $data = [];
        foreach ($this->_getActionFlagsValues() as $actionFlags) {
            foreach ($this->_getOrderStatuses() as $status) {
                $data[] = [$actionFlags, $status];
            }
        }
        return $data;
    }

    public function dataProviderActionFlag()
    {
        return [
            [false],
            [true]
        ];
    }

    /**
     * test method getIncrementId()
     */
    public function testGetIncrementId()
    {
        $this->assertEquals($this->incrementId, $this->order->getIncrementId());
    }

    public function testGetEntityType()
    {
        $this->assertEquals('order', $this->order->getEntityType());
    }

    /**
     * Run test getStatusHistories method
     *
     * @return void
     */
    public function testGetStatusHistories()
    {
        $itemMock = $this->getMockForAbstractClass(
            'Magento\Sales\Api\Data\OrderStatusHistoryInterface',
            [],
            '',
            false,
            true,
            true,
            ['setOrder']
        );
        $dbMock = $this->getMockBuilder('Magento\Framework\Data\Collection\AbstractDb')
            ->setMethods(['setOrder'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $collectionMock = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\Status\History\Collection',
            [
                'setOrderFilter',
                'setOrder',
                'getItems',
                'getIterator',
                'toOptionArray',
                'count',
                'load'
            ],
            [],
            '',
            false
        );

        $collectionItems = [$itemMock];

        $collectionMock->expects($this->once())
            ->method('setOrderFilter')
            ->with($this->order)
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('setOrder')
            ->with('created_at', 'desc')
            ->willReturn($dbMock);
        $dbMock->expects($this->once())
            ->method('setOrder')
            ->with('entity_id', 'desc')
            ->willReturn($collectionMock);
        $collectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn($collectionItems);

        $this->historyCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        for ($i = 10; --$i;) {
            $this->assertEquals($collectionItems, $this->order->getStatusHistories());
        }
    }

    public function testLoadByIncrementIdAndStoreId()
    {
        $incrementId = '000000001';
        $storeId = '2';
        $this->salesOrderCollectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->salesOrderCollectionMock);
        $this->salesOrderCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->salesOrderCollectionMock->expects($this->once())->method('load')->willReturnSelf();
        $this->salesOrderCollectionMock->expects($this->once())->method('getFirstItem')->willReturn($this->order);
        $this->assertSame($this->order, $this->order->loadByIncrementIdAndStoreId($incrementId, $storeId));
    }

    public function notInvoicingStatesProvider()
    {
        return [
            [\Magento\Sales\Model\Order::STATE_COMPLETE],
            [\Magento\Sales\Model\Order::STATE_CANCELED],
            [\Magento\Sales\Model\Order::STATE_CLOSED],
            [\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW]
        ];
    }

    public function canNotCreditMemoStatesProvider()
    {
        return [
            [\Magento\Sales\Model\Order::STATE_HOLDED],
            [\Magento\Sales\Model\Order::STATE_CANCELED],
            [\Magento\Sales\Model\Order::STATE_CLOSED],
            [\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW]
        ];
    }
}
