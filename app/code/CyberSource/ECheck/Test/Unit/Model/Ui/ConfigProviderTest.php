<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Model\Ui;

use CyberSource\ECheck\Model\Ui\ConfigProvider;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $config;

    private $counter = 0;
    
    public function setUp()
    {
        Bootstrap::create(BP, $_SERVER)->createApplication(Http::class);
        $this->configMock = $this->getMockBuilder(\CyberSource\ECheck\Gateway\Config\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assetRepoMock = $this->getMockBuilder(\Magento\Framework\View\Asset\Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this
            ->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->config = $helper->getObject(
            \CyberSource\ECheck\Model\Ui\ConfigProvider::class,
            [
                'config' => $this->configMock,
                'assetRepo' => $this->assetRepoMock,
                'logger' => $this->loggerMock,
                'request' => $this->requestMock,
                'urlBuilder' => $this->urlBuilder,
            ]
        );
    }
    
    public function testGetECheckImageUrl()
    {
        $this->assertEquals(null, $this->config->getECheckImageUrl());
    }
    
    public function testGetViewFileUrl()
    {
        $this->requestMock
             ->method('isSecure')
             ->will($this->returnCallback(function () {
                 $this->counter++;
                if ($this->counter == 2) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('error'));
                } else {
                    return null;
                }
             }));
        $this->assertEquals(null, $this->config->getViewFileUrl(1, []));
        $this->assertEquals(null, $this->config->getViewFileUrl(1, []));
    }

    public function testGetConfig()
    {
        $this->assertEquals(
            [
                'payment' => [
                    ConfigProvider::CODE => [
                        'isActive' => null,
                        'title' => null,
                        'echeckImage' => null
                    ]
                ]
            ],
            $this->config->getConfig()
        );
    }
}
