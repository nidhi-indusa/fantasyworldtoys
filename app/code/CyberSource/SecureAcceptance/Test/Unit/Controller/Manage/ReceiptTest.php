<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\SecureAcceptance\Test\Unit\Controller\Manage;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\IframeConfigProvider;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;

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
     * @var \CyberSource\SecureAcceptance\Controller\Manage\Receipt
     */
    private $controller;
    
    private $counter = 0;
    
    protected function setUp()
    {
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
        $this->quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock
            ->method('getBillingAddress')
            ->will($this->returnValue($this->billingAddressMock));
        $this->customerSessionMock = $this
            ->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock = $this->getMock('\Magento\Checkout\Model\Session', ['getIsMultiShipping'], [], '', false);
        $this->checkoutSessionMock
            ->method('getIsMultiShipping')
            ->will($this->returnValue(false));
        $this->objectManagerMock = $this
            ->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->getMockForAbstractClass();
        $this->eventManagerMock = $this
            ->getMockBuilder(\Magento\Framework\Event::class)
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
        $this->tokenMock = $this
            ->getMockBuilder(\CyberSource\SecureAcceptance\Model\Token::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->regionMock = $this
            ->getMockBuilder(\Magento\Directory\Model\Region::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->controller = $helper->getObject(
            \CyberSource\SecureAcceptance\Controller\Manage\Receipt::class,
            [
                'context' => $this->contextMock,
                'messageManager' => $this->messageManagerMock,
                'cyberSourceAPI' => $this->cybersourceApiMock,
                'checkoutSession' => $this->checkoutSessionMock,
                '_request' => $this->requestMock,
                '_response' => $this->responseMock,
                '_objectManager' => $this->objectManagerMock,
                '_eventManager' => $this->eventManagerMock,
                'coreRegistry' => $this->coreRegistryMock,
                'cartManagement' => $this->cartManagementMock,
                'onepageCheckout' => $this->onepageCheckout,
                'jsonHelper' => $this->jsonHelperMock,
                'resultFactory' => $this->resultFactoryMock,
                'storeManager' => $this->storeManagerMock,
                '_url' => $this->urlMock,
                'scopeConfig' => $this->scopeConfigMock,
                '_redirect' => $this->redirectMock,
                'customerSession' => $this->customerSessionMock,
                'token' => $this->tokenMock,
                'region' => $this->regionMock
            ]
        );
    }
    
    public function testExecute()
    {
        $this->dataOk = [
            \CyberSource\SecureAcceptance\Helper\RequestDataBuilder::REQ_USE_IFRAME => 1,
            'decision' => 'ok',
            'reason_code' => 100,
            'req_payment_token' => '1',
            'payment_token' => '1',
            'req_merchant_defined_data10' => 1,
            'req_reference_number' => 1,
            'req_bill_to_forename' => 'x',
            'req_bill_to_company_name' => 'x',
            'req_bill_to_surname' => 'x',
            'company' => 'x',
            'req_bill_to_company_name' => 'x',
            'req_bill_to_address_line1' => 'x',
            'req_bill_to_address_city' => 'x',
            'req_bill_to_address_postal_code' => 'x',
            'req_bill_to_address_country' => 'x',
            'req_bill_to_address_country' => 'x',
            'req_bill_to_phone' => 'x',
            'req_bill_to_address_country' => 'CA',
            'req_bill_to_address_state' => 'BC',
            'req_bill_to_phone' => 1
        ];
        $this->dataDecline = $this->dataOk;
        $this->dataDecline['decision'] = 'DECLINE';
        $this->dataDecline['reason_code'] = '101';
        $this->dataReview = $this->dataOk;
        $this->dataReview['reason_code'] = '480';
        $this->dataReview[\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::REQ_USE_IFRAME] = null;
        
        $this->objectManagerMock
             ->method('get')
             ->with('Magento\Customer\Model\Session')
             ->will($this->returnValue($this->customerSessionMock));
        $this->resultMock
            ->method('setUrl')
            ->willReturn('test');
        $this->storeMock
            ->method('getBaseUrl')
            ->will($this->returnValue(''));
        $this->resultFactoryMock
            ->method('create')
            ->with('raw')
            ->willReturn($this->resultRawMock);
        $this->storeManagerMock
            ->method('getStore')
            ->will($this->returnValue($this->storeMock));
        $this->customerSessionMock
             ->method('getId')
             ->will($this->returnValue(1));
        $this->regionMock
             ->method('loadByCode')
             ->with('BC', 'CA')
             ->will($this->returnValue($this->regionMock));
        $this->regionMock
             ->method('getId')
             ->will($this->returnValue(1));
        $this->customerSessionMock
             ->method('getData')
             ->with('address_id')
             ->will($this->returnValue(1));
        $this->tokenMock
             ->method('getData')
             ->with('address_id')
             ->will($this->returnValue(1));
        $this->tokenMock
             ->method('getId')
             ->will($this->returnCallback(function () {
                return ($this->counter == 4) ? false : true;
             }));
        $this->tokenMock
            ->method('save')
            ->will($this->returnCallback(function () {
                if ($this->counter == 4) {
                    throw new \Exception('test exception');
                }
                return true;
            }));
        $this->cybersourceApiMock
             ->method('retrieveProfile')
             ->with(1, 1)
             ->will($this->returnValue(json_decode(json_encode([
                 'reasonCode' => 100,
                 'paySubscriptionRetrieveReply' => ['cardAccountNumber' => '1111']
             ]))));
        $this->requestMock
            ->method('getParams')
            ->will($this->returnCallback(function () {
                $this->counter++;
                $data = [
                    1 => $this->dataOk,
                    2 => $this->dataDecline,
                    3 => $this->dataReview,
                    4 => $this->dataOk,
                ];
                $data[4]['req_transaction_type'] = 'update_payment_token';
                return $data[$this->counter];
            }));
        $this->assertInstanceOf(Raw::class, $this->controller->execute());
    }
}
