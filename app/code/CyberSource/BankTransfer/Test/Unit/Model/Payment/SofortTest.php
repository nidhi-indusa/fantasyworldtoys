<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Test\Unit\Model\Payment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class PlaceTest
 * @codingStandardsIgnoreStart
 */
class SofortTest extends \PHPUnit_Framework_TestCase
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
        $this->paymentMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->disableOriginalConstructor()
            ->getMock();   
        $helper = new ObjectManager($this);
        $this->model = $helper->getObject(
            \CyberSource\BankTransfer\Model\Payment\Sofort::class
        );
    }
    
    public function testCapture()
    {
        $this->assertEquals($this->model, $this->model->capture($this->paymentMock, 11.11));
    }
}