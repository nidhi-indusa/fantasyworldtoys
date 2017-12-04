<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class PlaceTest
 * @codingStandardsIgnoreStart
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
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
        $helper = new ObjectManager($this);
        $this->model = $helper->getObject(
            \CyberSource\BankTransfer\Model\Config::class
        );
    }
    
    public function testIsActive()
    {
        $this->assertEquals(null, $this->model->isActive());
    }
    
    public function testIsMethodActive()
    {
        $this->assertEquals(null, $this->model->isMethodActive('test'));
    }
    
    public function testGetMethodTitle()
    {
        $this->assertEquals(null, $this->model->getMethodTitle('test'));
    }
    
    public function testGetCode()
    {
        $this->assertEquals('cybersource_bank_transfer', $this->model->getCode());
    }
}