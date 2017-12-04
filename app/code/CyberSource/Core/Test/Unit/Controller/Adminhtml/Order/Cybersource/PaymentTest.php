<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\Core\Test\Unit\Controller\Adminhtml\Order\Cybersource;

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

class PaymentTest extends \PHPUnit_Framework_TestCase
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
            ->setMethods([
                'setCcTransId',
                'setLastTransId',
                'save'
            ])
            ->getMock();
        $this->quoteMock
            ->method('getBillingAddress')
            ->will($this->returnValue($this->billingAddressMock));
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
            ->setMethods(['setPostValue', 'getPost', 'has', 'getPostValue'])
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
        $this->scopeConfigInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productHelperMock = $this
            ->getMockBuilder(\Magento\Catalog\Helper\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataObjectMock = $this
            ->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonDataMock = $this
            ->getMockBuilder(\Magento\Framework\Json\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->giftModelSaveMock = $this
            ->getMockBuilder(\Magento\GiftMessage\Model\Save::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setGiftmessages',
                'getAllowQuoteItems',
                'importAllowQuoteItemsFromProducts',
                '_getQuote',
                'setAllowQuoteItems'
            ])
            ->getMock();
        $helper = new ObjectManager($this);
        $this->controller = $helper->getObject(
            \CyberSource\Core\Controller\Adminhtml\Order\Cybersource\Payment::class,
            [
                'context' => $this->contextMock,
                'messageManager' => $this->messageManagerMock,
                'api' => $this->cybersourceApiMock,
                '_session' => $this->backendQuoteSessionMock,
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
                'scopeConfig' => $this->scopeConfigInterfaceMock,
            ]
        );
    }
    
    public function testExecute()
    {
        $this->resultMock
            ->method('setUrl')
            ->willReturn('test');
        $this->modelTokenMock
            ->method('load')
            ->will($this->returnValue($this->modelTokenMock));
        $this->modelTokenMock
            ->method('getData')
            ->will($this->returnValue(['token' => 1]));
        $this->storeMock
            ->method('getBaseUrl')
            ->will($this->returnValue(''));
        $this->storeManagerMock
            ->method('getStore')
            ->will($this->returnValue($this->storeMock));
        $this->giftModelSaveMock
            ->method('setGiftmessages')
            ->will($this->returnValue($this->giftModelSaveMock));
        $this->giftModelSaveMock
            ->method('getAllowQuoteItems')
            ->will($this->returnValue([]));
        $this->giftModelSaveMock
            ->method('importAllowQuoteItemsFromProducts')
            ->will($this->returnValue([]));
        $this->giftModelSaveMock
            ->method('setAllowQuoteItems')
            ->will($this->returnValue($this->giftModelSaveMock));
        $this->giftModelSaveMock
            ->method('_getQuote')
            ->will($this->returnValue($this->quoteMock));
        $this->cybersourceApiMock
            ->method('tokenPayment')
            ->will($this->returnCallback(function ($param) {
                if ($this->counter > 3) {
                    $data = [];
                } elseif ($this->counter > 2) {
                    $data = ['requestID' => 1, 'reasonCode' => 480];
                } else {
                    $data = ['requestID' => 1, 'reasonCode' => 101];
                }
                return json_decode(json_encode($data));
            }));
        $this->resultFactoryMock
            ->method('create')
            ->with(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->will($this->returnValue($this->resultMock));
        $this->requestMock
             ->method('getPost')
             ->will($this->returnCallback(function ($param) {
                switch ($param) {
                    case 'order':
                        if ($this->counter > 1) {
                            $data = ['send_confirmation' => ['id' => 1]];
                        } else {
                            $data = false;
                        }
                        break;
                    case 'payment':
                    case 'item':
                        $data = [['id' => 1]];
                        break;
                    default:
                        $data = $param;
                }
                return $data;
             }));
        $this->requestMock
             ->method('getParam')
             ->will($this->returnCallback(function ($param) {
                $this->counter++;
                switch ($param) {
                    case 'payment':
                        if ($this->counter > 4) {
                            $data = ['token' => 1, 'cvv' => 1];
                        } elseif ($this->counter > 1) {
                            $data = ['token' => 1, 'method' => 'cybersource', 'cvv' => 1];
                        } else {
                            $data = [];
                        }
                        break;
                    default:
                        $data = $param;
                }
                return $data;
             }));
        $this->scopeConfigInterfaceMock
             ->method('getValue')
             ->will($this->returnCallback(function ($param, $scope) {
                switch ($param) {
                    case 'payment/chcybersource/enable_cvv':
                        $data = 1;
                        break;
                    default:
                        $data = false;
                }
                return $data;
             }));
        $this->adminOrderCreateMock = $this
            ->getMockBuilder(\Magento\Sales\Model\AdminOrder\Create::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adminOrderCreateMock
            ->method('getQuote')
            ->will($this->returnValue($this->quoteMock));
        $this->adminOrderCreateMock
            ->method('setIsValidate')
            ->will($this->returnValue($this->adminOrderCreateMock));
        $this->adminOrderCreateMock
            ->method('importPostData')
            ->will($this->returnValue($this->adminOrderCreateMock));
        $this->adminOrderCreateMock
            ->method('createOrder')
            ->will($this->returnValue($this->orderMock));
        $this->adminOrderCreateMock
            ->method('getSession')
            ->will($this->returnValue($this->backendQuoteSessionMock));
        $this->backendQuoteSessionMock
            ->method('getOrder')
            ->will($this->returnValue($this->orderMock));
        $this->quoteMock
            ->method('getPayment')
            ->will($this->returnValue($this->quotePaymentMock));
        $this->orderMock
            ->method('getPayment')
            ->will($this->returnValue($this->orderPaymentMock));
        $this->orderPaymentMock
            ->method('setCcTransId')
            ->will($this->returnValue($this->orderPaymentMock));
        $this->orderPaymentMock
            ->method('setLastTransId')
            ->will($this->returnValue($this->orderPaymentMock));
        $this->orderPaymentMock
            ->method('save')
            ->will($this->returnValue($this->orderPaymentMock));
        $this->adminOrderCreateMock
            ->method('getShippingAddress')
            ->will($this->returnValue($this->quoteAddressMock));
        $this->productHelperMock
            ->method('addParamsToBuyRequest')
            ->will($this->returnValue($this->dataObjectMock));
        $this->objectManagerMock
             ->method('get')
             ->will($this->returnCallback(function ($param) {
                switch ($param) {
                    case 'Magento\Sales\Model\AdminOrder\Create':
                        $data = $this->adminOrderCreateMock;
                        break;
                    case 'Magento\Catalog\Helper\Product':
                        $data = $this->productHelperMock;
                        break;
                    case 'Magento\GiftMessage\Model\Save':
                        $data = $this->giftModelSaveMock;
                        break;
                    case 'Magento\Framework\Json\Helper\Data':
                        $data = $this->jsonDataMock;
                        break;
                    default:
                        $data = $param;
                }
                return $data;
             }));
        $this->assertEquals($this->resultMock, $this->controller->execute());
        $this->assertEquals($this->resultMock, $this->controller->execute());
        $this->assertEquals($this->resultMock, $this->controller->execute());
        $this->assertEquals($this->resultMock, $this->controller->execute());
        $this->assertEquals($this->resultMock, $this->controller->execute());
    }
}
