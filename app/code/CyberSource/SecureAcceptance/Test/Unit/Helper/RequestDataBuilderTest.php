<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\SecureAcceptance\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class RequestDataBuilderTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionMock;
    
    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSessionMock;
    
    protected function setUp()
    {
        Bootstrap::create(BP, $_SERVER)->createApplication(Http::class);
        $this->configMock = $this
            ->getMockBuilder(\CyberSource\SecureAcceptance\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperCart = $this
            ->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
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
        $this->helperCart
            ->method('getCart')
            ->will($this->returnValue($this->quoteMock));
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
            ->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $this->quoteItemMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock
            ->method('getAllVisibleItems')
            ->will($this->returnValue([
                $this->quoteItemMock
            ]));
        $this->quoteMock
            ->method('getAllItems')
            ->will($this->returnValue([
                $this->quoteItemMock
            ]));
        $this->quoteMock
            ->method('collectTotals')
            ->will($this->returnValue($this->quoteMock));
        $this->customerModelMock = $this
            ->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock
            ->method('isTestMode')
            ->will($this->returnValue(true));
        $this->configMock
            ->method('getPaymentAction')
            ->will($this->returnValue('authorize'));
        $helper = new ObjectManager($this);
        $this->helper = $helper->getObject(
            \CyberSource\SecureAcceptance\Helper\RequestDataBuilder::class,
            [
                'checkoutSession' => $this->checkoutSessionMock,
                'cs' => $this->checkoutSessionMock,
                'customerSession' => $this->customerSessionMock,
                'objectManager' => $this->objectManagerMock,
                'orderCollectionFactory' => $this->collectionFactoryMock,
                'gatewayConfig' => $this->configMock,
                '_request' => $this->requestMock,
                'customerModel' => $this->customerModelMock,
                'helperCart' => $this->helperCart,
            ]
        );
    }
    
    public function testBuildSilentRequestData()
    {
        $this->checkoutSessionMock
            ->method('getData')
            ->with('fingerprint_id')
            ->will($this->returnValue(1));
        $this->configMock
            ->method('isSilent')
            ->will($this->returnValue(true));

        $this->quoteMock
            ->method('getReservedOrderId')
            ->willReturn(111);

        $params = $this->helper->buildSilentRequestData(null, false, null);
        unset($params['transaction_uuid']);
        unset($params['signed_date_time']);
        unset($params['signature']);
        unset($params['transaction_type']);
        $data = $this->getData('testBuildSilentRequestData');
        unset($data['transaction_type']);
        $this->assertEquals($data, $params);
        $params = $this->helper->buildSilentRequestData(null, true, 'test');
        unset($params['transaction_uuid']);
        unset($params['signed_date_time']);
        unset($params['signature']);
        unset($params['transaction_type']);
        $data = $this->getData('testBuildSilentRequestData');
        unset($data['transaction_type']);
        $data['payment_token'] = 'test';
        $data['line_item_count'] = '0';
        $data['unsigned_field_names'] = 'payment_token,developer_id,developer_id,bill_to_address_line2,ship_to_forename,'
            .'ship_to_surname,ship_to_email,ship_to_address_line1,ship_to_address_line2,'
            .'ship_to_address_city,ship_to_address_postal_code,ship_to_address_state,'
            .'ship_to_address_country,ship_to_state,ship_to_country,'
            .'merchant_defined_data1,merchant_defined_data6,merchant_defined_data20,'
            .'merchant_defined_data21,merchant_defined_data22,merchant_defined_data23,consumer_id,customer_ip_address,line_item_count';
        $data['signed_field_names'] = 'access_key,profile_id,transaction_uuid,'
            .'signed_field_names,unsigned_field_names,signed_date_time,locale,'
            .'transaction_type,reference_number,amount,currency,payment_method,'
            .'bill_to_forename,bill_to_surname,bill_to_email,bill_to_phone,'
            .'bill_to_address_line1,bill_to_address_city,bill_to_address_state,'
            .'bill_to_address_country,bill_to_address_postal_code,'
            .'payer_auth_enroll_service_run,partner_solution_id,tax_amount,payment_token,device_fingerprint_id';
        $this->assertEquals($data, $params);
    }
    
    public function testBuildRequestData()
    {
        $this->quoteItemMock
            ->method('getPrice')
            ->will($this->returnValue(10.10));
        $this->configMock
             ->method('getUseIframe')
             ->will($this->returnValue(true));
        $this->quoteMock
             ->method('getCheckoutMethod')
             ->will($this->returnValue(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST));
        $this->quoteMock
            ->method('getReservedOrderId')
            ->willReturn('111');
        $this->requestMock
             ->method('getParam')
             ->with('token')
             ->will($this->returnValue(1));
        $params = $this->helper->buildRequestData(false, null);
        unset($params['transaction_uuid']);
        unset($params['signed_date_time']);
        unset($params['signature']);
        unset($params['transaction_type']);
        $data = $this->getData('testBuildRequestData');
        unset($data['transaction_type']);
        $this->assertEquals($data, $params);
        $params = $this->helper->buildRequestData(true, null);
        unset($params['transaction_uuid']);
        unset($params['signed_date_time']);
        unset($params['signature']);
        unset($params['merchant_defined_data11']);
        unset($params['transaction_type']);
        $data = $this->getData('testBuildRequestData');
        unset($data['transaction_type']);
        $this->assertEquals($data, $params);
    }
    
    public function testBuildDecisionManagerFields()
    {
        $this->collectionMock
             ->method('getFirstItem')
             ->will($this->returnValue($this->orderMock));
        $this->collectionMock
             ->method('getSize')
             ->will($this->returnValue(1));
        $this->collectionMock
             ->method('setOrder')
             ->with('created_at', 'desc')
             ->will($this->returnValue($this->collectionMock));
        $this->collectionMock
             ->method('addFieldToFilter')
             ->withConsecutive(['customer_id', null], ['customer_email', null])
             ->will($this->returnValue($this->collectionMock));
        $this->customerModelMock
             ->method('load')
             ->with(null)
             ->will($this->returnValue($this->customerModelMock));
        $this->customerSessionMock
             ->method('isLoggedIn')
             ->will($this->returnValue(true));
        $data = $this->helper->buildDecisionManagerFields($this->quoteMock, $this->addressMock);
        $fields = $this->getData('testBuildDecisionManagerFields');
        unset($data['merchant_defined_data5']);
        unset($fields['merchant_defined_data5']);
        $this->assertEquals($fields, $data);
    }
    
    private function getData($test)
    {
        $data = [];
        switch ($test) {
            case 'testBuildSilentRequestData':
                $data = [
                    'access_key' => '',
                    'profile_id' => '',
                    'signed_field_names' => 'access_key,profile_id,transaction_uuid,'
                        .'signed_field_names,unsigned_field_names,signed_date_time,'
                        .'locale,transaction_type,reference_number,amount,currency,payment_method,'
                        .'bill_to_forename,bill_to_surname,bill_to_email,bill_to_phone,'
                        .'bill_to_address_line1,bill_to_address_city,bill_to_address_state,'
                        .'bill_to_address_country,bill_to_address_postal_code,'
                        .'payer_auth_enroll_service_run,partner_solution_id,tax_amount,device_fingerprint_id',
                    'unsigned_field_names' => 'card_type,card_number,card_expiry_date,developer_id,developer_id,'
                        .'bill_to_address_line2,ship_to_forename,ship_to_surname,ship_to_email,'
                        .'ship_to_address_line1,ship_to_address_line2,ship_to_address_city,ship_to_address_postal_code,'
                        .'ship_to_address_state,ship_to_address_country,ship_to_state,'
                        .'ship_to_country,merchant_defined_data1,merchant_defined_data6,'
                        .'merchant_defined_data20,merchant_defined_data21,'
                        .'merchant_defined_data22,merchant_defined_data23,consumer_id,customer_ip_address,line_item_count',
                    'locale' => '',
                    'transaction_type' => 'authorization,create_payment_token',
                    'reference_number' => '111',
                    'amount' => '0.00',
                    'currency' => 'USD',
                    'payment_method' => 'card',
                    'payer_auth_enroll_service_run' => 'true',
                    'bill_to_forename' => '',
                    'bill_to_surname' => '',
                    'bill_to_email' => '',
                    'bill_to_phone' => '',
                    'bill_to_address_line1' => '',
                    'bill_to_address_city' => '',
                    'bill_to_address_postal_code' => '',
                    'bill_to_address_state' => '',
                    'bill_to_address_country' => '',
                    'ship_to_forename' => '',
                    'ship_to_surname' => '',
                    'ship_to_email' => '',
                    'ship_to_address_line1' => '',
                    'ship_to_address_city' => '',
                    'ship_to_address_postal_code' => '',
                    'ship_to_address_state' => '',
                    'ship_to_address_country' => '',
                    'ship_to_state' => '',
                    'ship_to_country' => '',
                    'merchant_defined_data1' => '0',
                    'merchant_defined_data6' => '0',
                    'merchant_defined_data20' => '',
                    'merchant_defined_data21' => '0',
                    'merchant_defined_data22' => '',
                    'merchant_defined_data23' => 'web',
                    'device_fingerprint_id' => '1',
                    'line_item_count' => '0',
                    'partner_solution_id' => 'T54H9OLO',
                    'consumer_id' => '',
                    'customer_ip_address' => '',
                    'tax_amount' => '0.00',
                    'developer_id' => '',
                    'bill_to_address_line2' => '',
                    'ship_to_address_line2' => '',
                ];
                break;
            case 'testBuildRequestData':
                $data = [
                    'access_key' => null,
                    'profile_id' => null,
                    'ignore_avs' => null,
                    'ignore_cvn' => null,
                    'payment_method' => 'card',
                    'card_number' => '',
                    'signed_field_names' => 'access_key,profile_id,ignore_avs,'
                        .'ignore_cvn,transaction_uuid,signed_field_names,'
                        .'unsigned_field_names,signed_date_time,locale,transaction_type,'
                        .'reference_number,amount,currency,override_custom_receipt_page,'
                        .'override_custom_cancel_page,partner_solution_id,tax_amount,card_number,payment_token,customer_cookies_accepted',
                    'unsigned_field_names' =>
                        'payment_method,request_url,merchant_defined_data5,'.
                        'merchant_defined_data6,auth_indicator,bill_to_forename,'.
                        'bill_to_surname,bill_to_email,bill_to_phone,bill_to_address_line1,'.
                        'bill_to_address_line2,bill_to_address_city,bill_to_address_postal_code,'.
                        'bill_to_address_state,bill_to_address_country,bill_address1,'.
                        'bill_address2,bill_city,bill_country,ship_to_forename,ship_to_surname,'.
                        'ship_to_email,ship_to_address_line1,ship_to_address_line2,'.
                        'ship_to_address_city,ship_to_address_postal_code,ship_to_address_state,'.
                        'ship_to_address_country,merchant_defined_data1,merchant_defined_data20,'.
                        'merchant_defined_data21,merchant_defined_data22,merchant_defined_data23,'.
                        'consumer_id,customer_ip_address,item_0_name,item_0_sku,item_0_quantity,'.
                        'item_0_unit_price,item_0_tax_amount,line_item_count,customer_email,'.
                        'customer_lastname,developer_id',
                    'locale' => '',
                    'transaction_type' => 'authorization',
                    'reference_number' => '111',
                    'amount' => '0.00',
                    'currency' => 'USD',
                    'request_url' => 'https://testsecureacceptance.cybersource.com/pay',
                    'override_custom_receipt_page' => null,
                    'override_custom_cancel_page' => null,
                    'tax_amount' => '0.00',
                    'merchant_defined_data5' => 'Discount amount: 0',
                    'merchant_defined_data6' => 0,
                    'partner_solution_id' => 'T54H9OLO',
                    'auth_indicator' => null,
                    'bill_to_forename' => null,
                    'bill_to_surname' => null,
                    'bill_to_email' => null,
                    'bill_to_phone' => '',
                    'bill_to_address_line1' => null,
                    'bill_to_address_line2' => null,
                    'bill_to_address_city' => null,
                    'bill_to_address_postal_code' => null,
                    'bill_to_address_state' => null,
                    'bill_to_address_country' => null,
                    'bill_address1' => null,
                    'bill_address2' => null,
                    'bill_city' => null,
                    'bill_country' => null,
                    'ship_to_forename' => null,
                    'ship_to_surname' => null,
                    'ship_to_email' => null,
                    'ship_to_address_line1' => null,
                    'ship_to_address_city' => null,
                    'ship_to_address_postal_code' => null,
                    'ship_to_address_state' => null,
                    'ship_to_address_country' => null,
                    'merchant_defined_data1' => 0,
                    'merchant_defined_data20' => null,
                    'merchant_defined_data21' => 0,
                    'merchant_defined_data22' => '',
                    'merchant_defined_data23' => 'web',
                    'line_item_count' => 1,
                    'customer_email' => null,
                    'customer_lastname' => null,
                    'payment_token' => 1,
                    'use_iframe' => 1,
                    'item_0_name' => null,
                    'item_0_sku' => null,
                    'item_0_quantity' => null,
                    'item_0_unit_price' => '10.10',
                    'item_0_tax_amount' => '0.00',
                    'consumer_id' => null,
                    'customer_ip_address' => null,
                    'ship_to_address_line2' => null,
                    'developer_id' => null,
                    'customer_cookies_accepted' => 'false',
                ];
                break;
            case 'testBuildDecisionManagerFields':
                $data = [
                    'merchant_defined_data1' => 1,
                    'merchant_defined_data2' => null,
                    'merchant_defined_data3' => 0,
                    'merchant_defined_data4' => null,
                    'merchant_defined_data6' => 0,
                    'merchant_defined_data20' => null,
                    'merchant_defined_data21' => 0,
                    'merchant_defined_data22' => '',
                    'merchant_defined_data23' => 'web',
                    'consumer_id' => null,
                    'customer_ip_address' => null,
                ];
                break;
        }
        return $data;
    }
}
