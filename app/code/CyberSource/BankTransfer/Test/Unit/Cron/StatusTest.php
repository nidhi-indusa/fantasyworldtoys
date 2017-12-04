<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Test\Unit\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class PlaceTest
 * @codingStandardsIgnoreStart
 */
class StatusTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;
    
    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $model;
    
    public function setUp()
    {
        $this->selectMock = $this
            ->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Payment\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock
            ->method('getSelect')
            ->will($this->returnValue($this->selectMock));
        $this->collectionFactoryMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();  
        $this->collectionFactoryMock
            ->method('create')
            ->will($this->returnValue($this->collectionMock));
        $helper = new ObjectManager($this);
        $this->model = $helper->getObject(
            \CyberSource\BankTransfer\Cron\Status::class,
            [
                'paymentCollectionFactory' => $this->collectionFactoryMock
            ]
        );
    }
    
    public function testExecute()
    {
        $this->assertEquals($this->model, $this->model->execute());
    }
}