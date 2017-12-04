<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Command;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class CaptureCallerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Bootstrap::create(BP, $_SERVER)->createApplication(Http::class);
        $this->configMock = $this
            ->getMockBuilder(\CyberSource\SecureAcceptance\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->infoInterfaceMock = $this
            ->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commandPoolMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commandInterfaceMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->gateway = $this->getMockForAbstractClass(\CyberSource\ECheck\Gateway\Command\CaptureCaller::class, [
            'commandPool' => $this->commandPoolMock,
        ]);
    }
    
    public function testCapture()
    {
        $this->commandPoolMock
             ->method('get')
             ->will($this->returnValue($this->commandInterfaceMock));
        $this->assertEquals(null, $this->gateway->capture($this->infoInterfaceMock, 1));
    }
}
