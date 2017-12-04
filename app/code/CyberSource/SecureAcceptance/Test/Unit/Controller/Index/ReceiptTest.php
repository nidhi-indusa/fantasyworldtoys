<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\SecureAcceptance\Test\Unit\Controller\Index;

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
use Magento\Framework\App\Bootstrap;

class ReceiptTest extends \PHPUnit_Framework_TestCase
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
     * @var \CyberSource\BankTransfer\Controller\Index\Address
     */
    private $controller;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlMock;
    
    private $counter = 0;
    
    protected function setUp()
    {
        Bootstrap::create(BP, $_SERVER)->createApplication(\Magento\Framework\App\Http::class);
        $this->responseInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectMock = $this
            ->getMockBuilder(\Magento\Framework\App\Response\RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigMock = $this
            ->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cybersourceApiMock = $this
            ->getMockBuilder(\CyberSource\Core\Service\CyberSourceSoapAPI::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->billingAddressMock = $this
            ->getMockBuilder(\Magento\Quote\Api\Data\AddressInterface::class)
            ->getMock();
        $this->shippingAddressMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->statusMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Status::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->orderPaymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->willReturn([]);

        $this->orderMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock
            ->method('getPayment')
            ->will($this->returnValue($this->orderPaymentMock));
        $this->paymentMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock
            ->method('getBillingAddress')
            ->will($this->returnValue($this->billingAddressMock));
        $this->quoteMock
            ->method('getShippingAddress')
            ->will($this->returnValue($this->shippingAddressMock));
        $this->quoteMock
            ->method('getId')
            ->will($this->returnValue(1));
        $this->quoteMock
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));
        $this->customerSessionMock = $this
            ->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock = $this
            ->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock
            ->method('getId')
            ->willReturn(1);
        $this->customerSessionMock
            ->method('getCustomer')
            ->willReturn($customerMock);
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
        $this->resultRawMock = $this
            ->getMockBuilder(\Magento\Framework\Controller\Result\Raw::class)
            ->getMock();
        $this->jsonHelperMock = $this
            ->getMockBuilder(\Magento\Framework\Json\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this
            ->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
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
            ->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManagerMock = $this
            ->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteTotalMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\TotalsCollector::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteManagementMock = $this
            ->getMockBuilder(\Magento\Quote\Model\QuoteManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderItemMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cybersourceHelperMock = $this
            ->getMockBuilder(\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenMock = $this
            ->getMockBuilder(\CyberSource\SecureAcceptance\Model\Token::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteFactoryMock = $this
            ->getMockBuilder(\Magento\Quote\Model\QuoteFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteCollectionMock = $this
            ->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteManagementMock
             ->method('submit')
             ->with($this->quoteMock)
             ->will($this->returnValue($this->orderMock));
        $this->statusMock
             ->method('loadDefaultByState')
             ->will($this->returnValue($this->statusMock));
        $helper = new ObjectManager($this);
        $this->controller = $helper->getObject(
            \CyberSource\SecureAcceptance\Controller\Index\Receipt::class,
            [
                'context' => $this->contextMock,
                'messageManager' => $this->messageManagerMock,
                'cyberSourceAPI' => $this->cybersourceApiMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'customerSession' => $this->customerSessionMock,
                '_request' => $this->requestMock,
                'response' => $this->responseMock,
                '_objectManager' => $this->objectManagerMock,
                'coreRegistry' => $this->coreRegistryMock,
                'cartManagement' => $this->cartManagementMock,
                'onepageCheckout' => $this->onepageCheckout,
                'jsonHelper' => $this->jsonHelperMock,
                'resultFactory' => $this->resultFactoryMock,
                'storeManager' => $this->storeManagerMock,
                '_url' => $this->urlMock,
                'scopeConfig' => $this->scopeConfigMock,
                '_redirect' => $this->redirectMock,
                'quoteManagement' => $this->quoteManagementMock,
                'cart' => $this->cartMock,
                'helper' => $this->cybersourceHelperMock,
                'token' => $this->tokenMock,
                'quoteFactory' => $this->quoteFactoryMock,
                'status' => $this->statusMock
            ]
        );
    }
    
    public function testExecute()
    {
        $this->dataOk = [
            'decision' => 'OK',
            'reason_code' => 100,
            'req_reference_number' => 1,
            'transaction_id' => 1,
            'req_card_type' => 'visa',
            'req_card_expiry_date' => '1010',
            'req_transaction_type' => 'authorization,create_payment_token',
            'request_token' => '111',
            'req_payment_token' => '111',
            'req_amount' => 10.0,
            'req_tax_amount' => 10.0,
            'req_bill_to_email' => 'test',
            'req_item_0_tax_amount' => 10.00,
            'payment_token' => 1,
            'req_card_number' => 1111,
            'req_item_0_unit_price' => 10.0,
            'req_item_0_tax_amount' => 0.0,
        ];
        $this->dataOk2 = $this->dataOk;
        unset($this->dataOk2['req_payment_token']);
        $this->reviewData = $this->dataOk;
        $this->reviewData['decision'] = 'REVIEW';
        $this->reviewData['reason_code'] = 480;
        $this->reviewData2 = $this->reviewData;
        unset($this->reviewData2['req_payment_token']);
        unset($this->reviewData2['payment_token']);
        
        $this->quoteFactoryMock
             ->method('create')
             ->will($this->returnValue($this->quoteMock));
        $this->quoteMock
             ->method('getCollection')
             ->will($this->returnValue($this->quoteCollectionMock));
        $this->quoteCollectionMock
             ->method('getFirstItem')
             ->will($this->returnValue($this->quoteMock));
        
        $this->objectManagerMock
             ->method('create')
             ->with('Magento\Framework\UrlInterface')
             ->will($this->returnValue($this->urlMock));
        $this->resultMock
            ->method('setUrl')
            ->willReturn('test');
        $this->storeManagerMock
            ->method('getStore')
            ->will($this->returnValue($this->storeMock));
        $this->storeMock
            ->method('getBaseUrl')
            ->will($this->returnValue(''));
        $this->resultFactoryMock
            ->method('create')
            ->with('raw')
            ->will($this->returnValue($this->resultRawMock));
        $this->quoteMock
            ->method('collectTotals')
            ->will($this->returnValue($this->quoteMock));
        $this->orderMock
            ->method('getAllVisibleItems')
            ->will($this->returnValue([
                $this->orderItemMock
            ]));
        $this->orderItemMock
            ->method('getPrice')
            ->will($this->returnValue(10.00));
        $this->cartMock
            ->method('truncate')
            ->will($this->returnValue($this->cartMock));
        $this->objectManagerMock
            ->method('get')
            ->with('Magento\Customer\Model\Session')
            ->will($this->returnValue($this->customerSessionMock));
        $this->cybersourceHelperMock->expects($this->at(1))
            ->method('getPayerAuthenticationData')
            ->with($this->reviewData2)
            ->will($this->returnValue(['test' => 'ok']));
        $this->cybersourceHelperMock->expects($this->at(2))
            ->method('getPayerAuthenticationData')
            ->with($this->dataOk)
            ->will($this->returnValue(['test' => 'ok']));
        $this->requestMock
             ->method('getParams')
             ->will($this->returnCallback(function () {
                $this->counter++;
                $data = [
                    1 => ['decision' => 'DECLINE', 'reason_code' => 101],
                    2 => ['decision' => 'ERROR', 'reason_code' => 101, 'message' => 'error'],
                    3 => $this->reviewData,
                    4 => $this->reviewData2,
                    5 => $this->dataOk,
                    6 => $this->dataOk2,
                ];
                return $data[$this->counter];
             }));
        $this->customerSessionMock
             ->method('isLoggedIn')
             ->will($this->returnCallback(function () {
                 return (in_array($this->counter, [5, 6])) ? true : false;
             }));
        $this->checkoutSessionMock
             ->method('getData')
             ->will($this->returnCallback(function ($param) {
                if ($param == 'isRequestAuthorizeType') {
                    return (in_array($this->counter, [4, 5])) ? true : false;
                } else {
                    return null;
                }
             }));
        $this->tokenMock
            ->method('save')
            ->will($this->returnCallback(function () {
                throw new \Exception('test exception');
            }));
        $this->messageManagerMock
            ->method('addSuccessMessage')
            ->with(__('Your order has been successfully created!'))
            ->will($this->returnCallback(function () {
                throw new \Exception('test exception');
            }));
        $this->assertEquals($this->resultRawMock, $this->controller->execute());
        $this->assertEquals($this->resultRawMock, $this->controller->execute());
        try {
            $this->controller->execute();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->assertEquals('test exception', $e->getMessage());
        }
        $this->resultRawMock->item = [];
        $this->assertEquals($this->resultRawMock, $this->controller->execute());
        $this->assertEquals($this->resultRawMock, $this->controller->execute());
    }
}
