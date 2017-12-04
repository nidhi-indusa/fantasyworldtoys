<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\Core\Test\Unit\Controller\Adminhtml\Taxclass;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\IframeConfigProvider;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;

class NewActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Place
     */
    protected $placeOrderController;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var CartManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartManagementMock;

    /**
     * @var Onepage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $onepageCheckout;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jsonHelperMock;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteMock;

    /**
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $billingAddressMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cybersourceApiMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jsonFactoryMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;
    
    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;
    
    /**
     *
     * @var \CyberSource\BankTransfer\Controller\Index\Address
     */
    private $controller;
    
    private $counter = 0;
    
    protected function setUp()
    {
        $this->cybersourceApiMock = $this
            ->getMockBuilder(\CyberSource\Core\Service\CyberSourceSoapAPI::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->billingAddressMock = $this
            ->getMockBuilder(\Magento\Quote\Api\Data\AddressInterface::class)
            ->getMock();
        $this->quoteAddressMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quotePaymentMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderPaymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock
            ->method('getBillingAddress')
            ->will($this->returnValue($this->billingAddressMock));
        $this->backendSessionMock = $this
            ->getMockBuilder(\Magento\Backend\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->backendQuoteSessionMock = $this
            ->getMockBuilder(\Magento\Backend\Model\Session\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock
            ->method('getQuote')
            ->will($this->returnValue($this->quoteMock));
        $this->objectManagerMock = $this
            ->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->getMockForAbstractClass();
        $this->coreRegistryMock = $this
            ->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartManagementMock = $this
            ->getMockBuilder(\Magento\Quote\Api\CartManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->onepageCheckout = $this
            ->getMockBuilder(\Magento\Checkout\Model\Type\Onepage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock = $this
            ->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this
            ->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonHelperMock = $this
            ->getMockBuilder(\Magento\Framework\Json\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this
            ->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPostValue', 'getPost', 'has', 'getPostValue', 'initForward', 'setDispatched'])
            ->getMockForAbstractClass();
        $this->responseMock = $this
            ->getMockBuilder(\Magento\Framework\App\Response\Http::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this
            ->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeMock = $this
            ->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock = $this
            ->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManagerMock = $this
            ->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventManagerInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->modelTokenMock = $this
            ->getMockBuilder(\CyberSource\Core\Model\Token::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ulrInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageTitleMock = $this
            ->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageConfigMock = $this
            ->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageResultMock = $this
            ->getMockBuilder(\Magento\Backend\Model\View\Result\Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageFactoryMock = $this
            ->getMockBuilder(\Magento\Framework\View\Result\PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->viewMock = $this
            ->getMockBuilder(\Magento\Framework\App\ViewInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->layoutMock = $this
            ->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messagesBlockMock = $this
            ->getMockBuilder(\Magento\Framework\View\Element\Messages::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messagesCollectionMock = $this
            ->getMockBuilder(\Magento\Framework\Message\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authMock = $this
            ->getMockBuilder(\Magento\Framework\AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionFlagMock = $this
            ->getMockBuilder(\Magento\Framework\App\ActionFlag::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManagerMock
            ->method('getMessages')
            ->will($this->returnValue($this->messagesCollectionMock));
        $this->layoutMock
            ->method('getMessagesBlock')
            ->will($this->returnValue($this->messagesBlockMock));
        $this->viewMock
            ->method('getLayout')
            ->will($this->returnValue($this->layoutMock));
        $this->pageConfigMock
            ->method('getTitle')
            ->will($this->returnValue($this->pageTitleMock));
        $this->pageResultMock
            ->method('getConfig')
            ->will($this->returnValue($this->pageConfigMock));
        $this->pageFactoryMock
            ->method('create')
            ->will($this->returnValue($this->pageResultMock));
        $helper = new ObjectManager($this);
        $this->controller = $helper->getObject(
            \CyberSource\Core\Controller\Adminhtml\Taxclass\NewAction::class,
            [
                'context' => $this->contextMock,
                'messageManager' => $this->messageManagerMock,
                'cyberSourceAPI' => $this->cybersourceApiMock,
                'session' => $this->checkoutSessionMock,
                '_request' => $this->requestMock,
                '_response' => $this->responseMock,
                '_objectManager' => $this->objectManagerMock,
                '_eventManager' => $this->eventManagerInterfaceMock,
                'coreRegistry' => $this->coreRegistryMock,
                'cartManagement' => $this->cartManagementMock,
                'onepageCheckout' => $this->onepageCheckout,
                'jsonHelper' => $this->jsonHelperMock,
                'resultFactory' => $this->resultFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'modelToken' => $this->modelTokenMock,
                '_url' => $this->ulrInterfaceMock,
                '_resultPageFactory' => $this->pageFactoryMock,
                '_view' => $this->viewMock,
                '_authorization' => $this->authMock,
                '_session' => $this->backendSessionMock,
                '_actionFlag' => $this->actionFlagMock,
            ]
        );
    }
    
    public function testExecute()
    {
        $this->assertEquals(null, $this->controller->execute());
    }
}
