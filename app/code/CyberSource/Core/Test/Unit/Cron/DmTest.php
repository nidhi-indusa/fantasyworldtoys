<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\Core\Test\Unit\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class DmTest extends \PHPUnit_Framework_TestCase
{
    
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
            ->getMockBuilder(\CyberSource\Core\Model\Token::class)
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
        $this->curlMock = $this
            ->getMockBuilder(\Magento\Framework\HTTP\Client\Curl::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentCollectionFactoryMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentCollectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Payment\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentModelMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Api\OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cybersourceApiMock = $this
            ->getMockBuilder(\CyberSource\Core\Service\CyberSourceSoapAPI::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this
            ->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeMock = $this
            ->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transportBuilderMock = $this
            ->getMockBuilder(\Magento\Framework\Mail\Template\TransportBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transportMock = $this
            ->getMockBuilder(\Magento\Framework\Mail\TransportInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->invoiceServiceMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Service\InvoiceService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->invoiceMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Invoice::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->dm = $helper->getObject(
            \CyberSource\Core\Cron\Dm::class,
            [
                'checkoutSession' => $this->checkoutSessionMock,
                'customerSession' => $this->customerSessionMock,
                'objectManager' => $this->objectManagerMock,
                'salesOrderCollectionFactory' => $this->collectionFactoryMock,
                '_scopeConfig' => $this->configMock,
                '_request' => $this->requestMock,
                'customerModel' => $this->customerModelMock,
                '_address' => $this->addressMock,
                'token' => $this->tokenMock,
                'pageConfig' => $this->pageConfigMock,
                'moduleManager' => $this->moduleManagerMock,
                'curl' => $this->curlMock,
                'paymentCollectionFactory' => $this->paymentCollectionFactoryMock,
                'orderRepository' => $this->orderRepoMock,
                'cybersourceApi' => $this->cybersourceApiMock,
                'storeManager' => $this->storeManagerMock,
                'transportBuilder' => $this->transportBuilderMock,
                'invoiceService' => $this->invoiceServiceMock,
                'data' => [],
            ]
        );
    }
    
    public function testExecute()
    {
        $this->tokenMock
             ->method('save')
             ->will($this->returnCallback(function () {
                if ($this->counter == 4) {
                    throw new \Exception(__('error'));
                }
             }));
        $this->curlMock
             ->method('getBody')
             ->will($this->returnCallback(function () {
                 $this->counter++;
                 $response = file_get_contents(__DIR__.'/response'.$this->counter.'.xml');
                 return $response;
             }));
        $this->paymentCollectionFactoryMock
             ->method('create')
             ->will($this->returnValue($this->paymentCollectionMock));
        $items = new \ArrayObject;
        $items->append($this->paymentModelMock);
        $this->paymentCollectionMock
            ->method('getIterator')
            ->will($this->returnValue($items));
        $this->paymentModelMock
            ->method('getCcTransId')
            ->will($this->returnValue('4920128092456096403529'));
        $this->paymentModelMock
            ->method('getData')
            ->will($this->returnCallback(function ($param) {
                $data = null;
                if ($this->counter == 3 && $param == 'additional_information') {
                    $data = [
                        'payment_token' => 1
                    ];
                }
                if ($this->counter == 1 && $param == 'amount_paid') {
                    $data = 1;
                }
                return $data;
            }));
        $this->orderRepoMock
            ->method('get')
            ->will($this->returnValue($this->orderMock));
        $this->cybersourceApiMock
            ->method('convertToProfile')
            ->will($this->returnValue(json_decode(json_encode([
                'paySubscriptionCreateReply' => [
                    'subscriptionID' => 1,
                ],
                'reasonCode' => 100,
                'requestID' => 1,
                'merchantReferenceCode' => 1
            ]))));
        $this->cybersourceApiMock
            ->method('retrieveProfile')
            ->will($this->returnValue(json_decode(json_encode([
                'paySubscriptionRetrieveReply' => [
                    'cardAccountNumber' => 1
                ],
                'reasonCode' => 100,
            ]))));
        $this->invoiceServiceMock
            ->method('prepareInvoice')
            ->will($this->returnValue($this->invoiceMock));
        $this->invoiceMock
            ->method('register')
            ->will($this->returnValue($this->invoiceMock));
        $this->storeManagerMock
            ->method('getStore')
            ->will($this->returnValue($this->storeMock));
        $this->orderMock
            ->method('setStatus')
            ->will($this->returnValue($this->orderMock));
        $this->orderMock
            ->method('getStatus')
            ->will($this->returnCallback(function () {
                $data = null;
                if ($this->counter == 4) {
                    $data = 'review';
                }
                return $data;
            }));
        $this->transportBuilderMock
            ->method('setTemplateIdentifier')
            ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
            ->method('setTemplateOptions')
            ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
            ->method('setTemplateVars')
            ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
            ->method('setFrom')
            ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
            ->method('addTo')
            ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
            ->method('setReplyTo')
            ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
            ->method('getTransport')
            ->will($this->returnValue($this->transportMock));
        $this->assertEquals($this->dm, $this->dm->execute());
        $this->assertEquals($this->dm, $this->dm->execute());
        $this->assertEquals($this->dm, $this->dm->execute());
        $this->assertEquals($this->dm, $this->dm->execute());
    }
}
