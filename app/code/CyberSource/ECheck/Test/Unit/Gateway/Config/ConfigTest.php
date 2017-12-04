<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Config;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Bootstrap::create(BP, $_SERVER)->createApplication(Http::class);
        $this->configMock = $this
            ->getMockBuilder(\CyberSource\SecureAcceptance\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigMock = $this
            ->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->config = $helper->getObject(
            \CyberSource\ECheck\Gateway\Config\Config::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'pathPattern' => 'path',
                'methodCode' => 'echeck',
            ]
        );
    }
    
    public function testIsActive()
    {
        $this->assertEquals(null, $this->config->isActive());
    }
    
    public function testGetTitle()
    {
        $this->assertEquals(null, $this->config->getTitle());
    }
    
    public function testGetPaymentAction()
    {
        $this->assertEquals('authorize', $this->config->getPaymentAction());
    }
    
    public function testSetMethod()
    {
        $this->assertEquals(null, $this->config->setMethod('method'));
    }
    
    public function testGetConfigPaymentAction()
    {
        $this->assertEquals('authorize', $this->config->getConfigPaymentAction());
    }
    
    public function testGetMerchantId()
    {
        $this->assertEquals(null, $this->config->getMerchantId());
    }
    
    public function testGetMerchantPassword()
    {
        $this->assertEquals(null, $this->config->getMerchantPassword());
    }
    
    public function testGetMerchantUsername()
    {
        $this->assertEquals(null, $this->config->getMerchantUsername());
    }
    
    public function testIsTestMode()
    {
        $this->assertEquals(true, $this->config->isTestMode());
    }
    
    public function testGetServerUrl()
    {
        $this->assertEquals(null, $this->config->getServerUrl());
    }
}
