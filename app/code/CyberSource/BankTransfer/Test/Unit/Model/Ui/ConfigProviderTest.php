<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Test\Unit\Model\Ui;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class PlaceTest
 * @codingStandardsIgnoreStart
 */
class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;
    
    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeMock;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;
    
    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProviderMock;
    
    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;
    
    public function setUp()
    {
        $this->configMock = $this->getMockBuilder(\CyberSource\BankTransfer\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeMock = $this->getMockBuilder(\Magento\Framework\Locale\ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();        
        $helper = new ObjectManager($this);
        $this->configProvider = $helper->getObject(
            \CyberSource\BankTransfer\Model\Ui\ConfigProvider::class,
            [
                'config' => $this->configMock,
                'locale' => $this->localeMock,
            ]
        );
        
    }
    
    public function testGetConfig()
    {
        $activeCallback = function($bankcode){
            return true;
        };
        $titleCallback = function($bankcode){
            return $bankcode;
        };
        $this->configMock
            ->method('isMethodActive')
            ->will($this->returnCallback($activeCallback));
        $this->configMock
            ->method('getMethodTitle')
            ->will($this->returnCallback($titleCallback));
        
        $data = [
            'payment' => [
                'cybersource_bank_transfer_ideal' => [
                    'active' => true,
                    'title' => 'ideal',
                ],
                'cybersource_bank_transfer_sofort' => [
                    'active' => true,
                    'title' => 'sofort',
                ],
                'cybersource_bank_transfer_bancontact' => [
                    'active' => true,
                    'title' => 'bancontact',
                ],
            ]
        ];
        $this->assertEquals($data, $this->configProvider->getConfig());
    }
    
}