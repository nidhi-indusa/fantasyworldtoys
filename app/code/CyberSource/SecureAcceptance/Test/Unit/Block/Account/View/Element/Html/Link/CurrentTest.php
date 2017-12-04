<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\SecureAcceptance\Test\Unit\Block\View\Element\Html\Link;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class CurrentTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionMock;
    
    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSessionMock;
    
    private $counter = 0;
    
    protected function setUp()
    {
        Bootstrap::create(BP, $_SERVER)->createApplication(Http::class);
        $this->configMock = $this
            ->getMockBuilder(\CyberSource\SecureAcceptance\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock
            ->method('create')
            ->will($this->returnValue($this->collectionMock));
        $this->objectManagerMock = $this
            ->getMockBuilder(\Magento\Framework\App\ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->currencyMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Cart\Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock
            ->method('getBillingAddress')
            ->will($this->returnValue($this->addressMock));
        $this->quoteMock
            ->method('getShippingAddress')
            ->will($this->returnValue($this->addressMock));
        $this->checkoutSessionMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock
            ->method('getQuote')
            ->will($this->returnValue($this->quoteMock));
        $this->customerSessionMock = $this
            ->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperMock = $this
            ->getMockBuilder(\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock
            ->method('getId')
            ->will($this->returnValue(111));
        $this->currencyMock
            ->method('getData')
            ->with('store_currency_code')
            ->will($this->returnValue('USD'));
        $this->quoteMock
            ->method('getCurrency')
            ->will($this->returnValue($this->currencyMock));
        $this->requestMock = $this
            ->getMockBuilder(\Magento\Framework\HTTP\PhpEnvironment\Request::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteItemMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerModelMock = $this
            ->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->titleMock = $this
            ->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock
            ->method('isTestMode')
            ->will($this->returnValue(true));
        $this->configMock
            ->method('getPaymentAction')
            ->will($this->returnValue('authorize'));
        $this->pageConfigMock = $this
            ->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageConfigMock
             ->method('getTitle')
             ->will($this->returnValue($this->titleMock));
        $this->tokenMock = $this
            ->getMockBuilder(\CyberSource\SecureAcceptance\Model\Token::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenCollectionMock = $this
            ->getMockBuilder(\CyberSource\SecureAcceptance\Model\ResourceModel\Token\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->moduleManagerMock = $this
            ->getMockBuilder(\Magento\Framework\Module\Manager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->block = $helper->getObject(
            \CyberSource\SecureAcceptance\Block\View\Element\Html\Link\Current::class,
            [
                'checkoutSession' => $this->checkoutSessionMock,
                'customerSession' => $this->customerSessionMock,
                'objectManager' => $this->objectManagerMock,
                'orderCollectionFactory' => $this->collectionFactoryMock,
                '_scopeConfig' => $this->configMock,
                '_request' => $this->requestMock,
                'customerModel' => $this->customerModelMock,
                '_address' => $this->addressMock,
                'token' => $this->tokenMock,
                'pageConfig' => $this->pageConfigMock,
                'moduleManager' => $this->moduleManagerMock,
                'data' => [],
            ]
        );
    }
    
    public function test_toHtml()
    {
        $this->requestMock
             ->method('getControllerName')
             ->with('CyberSource_SecureAcceptance')
             ->will($this->returnValue(true));
        $this->moduleManagerMock
             ->method('isEnabled')
             ->with('CyberSource_SecureAcceptance')
             ->will($this->returnValue(true));
        $this->configMock
             ->method('getValue')
             ->with('payment/chcybersource/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
             ->will($this->returnCallback(function () {
                $this->counter++;
                return ($this->counter == 1);
             }));
        $this->assertEquals('<li class="nav item current"><strong></strong></li>', $this->block->_toHtml());
        $this->assertEquals('', $this->block->_toHtml());
    }
}
