<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Observer;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use CyberSource\ECheck\Gateway\Response\ReasonCodeHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class UpdateOrderStatusObserverTest extends \PHPUnit_Framework_TestCase
{
    
    protected function setUp()
    {
        Bootstrap::create(BP, $_SERVER)->createApplication(Http::class);
        $this->orderRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Model\OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentDataObjectMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultInterfaceMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentCollectionFactoryMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentCollectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Payment\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commandPoolMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock = $this
            ->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock = $this
            ->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->observer = $helper->getObject(
            \CyberSource\ECheck\Observer\UpdateOrderStatusObserver::class,
            [
                '_orderRepository' => $this->orderRepoMock,
            ]
        );
    }
    
    public function testValidate()
    {
        $this->observerMock
             ->method('getData')
             ->will($this->returnValue($this->orderMock));
        $this->orderMock
             ->method('getPayment')
             ->will($this->returnValue($this->paymentMock));
        $this->paymentMock
             ->method('getMethod')
             ->will($this->returnValue('cybersourceecheck'));
        $this->assertEquals(null, $this->observer->execute($this->observerMock));
    }
}
