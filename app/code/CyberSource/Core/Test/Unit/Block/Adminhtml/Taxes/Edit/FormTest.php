<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\Core\Test\Unit\Block\Adminhtml\Taxes\Edit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class FormTest extends \PHPUnit_Framework_TestCase
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
        $this->authSessionMock = $this
            ->getMockBuilder(\Magento\Backend\Model\Auth::class)
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
            ->setMethods(['getParam'])
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
        $this->abstractElementMock = $this
            ->getMockBuilder(\Magento\Framework\Data\Form\Element\AbstractElement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerModelMock = $this
            ->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupMock = $this
            ->getMockBuilder(\Magento\Config\Model\Config\Structure\Element\Group::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldsetMock = $this
            ->getMockBuilder(\Magento\Config\Block\System\Config\Form\Fieldset::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock = $this
            ->getMockBuilder(\Magento\Backend\Block\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this
            ->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeMock = $this
            ->getMockBuilder(\Magento\Store\Api\Data\StoreConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->coreRegistryMock = $this
            ->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderPaymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cyberHelperMock = $this
            ->getMockBuilder(\CyberSource\Core\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formFactoryMock = $this
            ->getMockBuilder(\Magento\Framework\Data\FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formMock = $this
            ->getMockBuilder(\Magento\Framework\Data\Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldsetMock = $this
            ->getMockBuilder(\Magento\Config\Block\System\Config\Form\Fieldset::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->taxModelMock = $this
            ->getMockBuilder(\Magento\Tax\Model\ClassModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->block = $helper->getObject(
            \CyberSource\Core\Block\Adminhtml\Taxes\Edit\Form::class,
            [
                'checkoutSession' => $this->checkoutSessionMock,
                '_authSession' => $this->authSessionMock,
                'objectManager' => $this->objectManagerMock,
                'orderCollectionFactory' => $this->collectionFactoryMock,
                '_scopeConfig' => $this->configMock,
                '_request' => $this->requestMock,
                'customerModel' => $this->customerModelMock,
                '_address' => $this->addressMock,
                'token' => $this->tokenMock,
                'pageConfig' => $this->pageConfigMock,
                'moduleManager' => $this->moduleManagerMock,
                'storeManager' => $this->storeManagerMock,
                '_coreRegistry' => $this->coreRegistryMock,
                'helper' => $this->cyberHelperMock,
                '_formFactory' => $this->formFactoryMock,
                'data' => [],
            ]
        );
    }

    
    public function test_PrepareForm()
    {
        $this->formFactoryMock
                ->method('create')
                ->will($this->returnValue($this->formMock));
        $this->formMock
                ->method('addFieldset')
                ->will($this->returnValue($this->fieldsetMock));
        $this->requestMock
             ->method('getParam')
             ->will($this->returnCallback(function ($param) {
                return $param;
             }));
        $this->coreRegistryMock
             ->method('registry')
             ->will($this->returnValue($this->taxModelMock));
        $this->taxModelMock
             ->method('load')
             ->will($this->returnValue($this->taxModelMock));
        $this->taxModelMock
             ->method('getData')
             ->will($this->returnValue(['id' => 1]));
        $this->assertEquals($this->block, $this->block->_prepareForm());
    }
}
