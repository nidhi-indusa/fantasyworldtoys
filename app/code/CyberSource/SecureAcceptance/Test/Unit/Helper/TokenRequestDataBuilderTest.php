<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\SecureAcceptance\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class TokenRequestDataBuilderTest extends \PHPUnit_Framework_TestCase
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
        $this->customerModelMock = $this
            ->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->regionMock = $this
            ->getMockBuilder(\Magento\Directory\Model\Region::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->regionMock
            ->method('load')
            ->with(null)
            ->will($this->returnValue($this->regionMock));
        $this->customerSessionMock
            ->method('getCustomer')
            ->will($this->returnValue($this->customerModelMock));
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
        $this->quoteMock
            ->method('getAllVisibleItems')
            ->will($this->returnValue([]));
        $this->quoteMock
            ->method('getAllItems')
            ->will($this->returnValue([]));
        $helper = new ObjectManager($this);
        $this->helper = $helper->getObject(
            \CyberSource\SecureAcceptance\Helper\TokenRequestDataBuilder::class,
            [
                'checkoutSession' => $this->checkoutSessionMock,
                'customerSession' => $this->customerSessionMock,
                'objectManager' => $this->objectManagerMock,
                'orderCollectionFactory' => $this->collectionFactoryMock,
                'gatewayConfig' => $this->configMock,
                'region' => $this->regionMock
            ]
        );
    }
    
    public function testBuildSilentRequestData()
    {
        $this->configMock
            ->method('isSilent')
            ->will($this->returnValue(true));
        $this->helper->buildTokenData($this->addressMock);
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
                        .'payer_auth_enroll_service_run',
                    'unsigned_field_names' => 'card_type,card_number,card_expiry_date,'
                        .'ship_to_forename,ship_to_surname,ship_to_email,'
                        .'ship_to_address_line1,ship_to_address_city,ship_to_address_postal_code,'
                        .'ship_to_address_state,ship_to_address_country,ship_to_state,'
                        .'ship_to_country,merchant_defined_data1,merchant_defined_data6,'
                        .'merchant_defined_data20,merchant_defined_data21,'
                        .'merchant_defined_data22,merchant_defined_data23',
                    'locale' => '',
                    'transaction_type' => 'sale,create_payment_token',
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
                    'merchant_defined_data23' => 'web'
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
                        .'override_custom_cancel_page,partner_solution_id,tax_amount,card_number',
                    'unsigned_field_names' => 'payment_method,request_url,merchant_defined_data5,'
                        .'merchant_defined_data6,auth_indicator,bill_to_forename,'
                        .'bill_to_surname,bill_to_email,bill_to_phone,bill_to_address_line1,'
                        .'bill_to_address_city,bill_to_address_postal_code,bill_to_address_state,'
                        .'bill_to_address_country,bill_address1,bill_city,bill_country,'
                        .'ship_to_forename,ship_to_surname,ship_to_email,ship_to_address_line1,'
                        .'ship_to_address_city,ship_to_address_postal_code,ship_to_address_state,'
                        .'ship_to_address_country,merchant_defined_data1,merchant_defined_data20,'
                        .'merchant_defined_data21,merchant_defined_data22,merchant_defined_data23,'
                        .'line_item_count,customer_email,customer_lastname',
                    'locale' => '',
                    'transaction_type' => 'sale,create_payment_token',
                    'reference_number' => 111,
                    'amount' => '0.00',
                    'currency' => 'USD',
                    'request_url' => 'https://secureacceptance.cybersource.com/pay',
                    'override_custom_receipt_page' => null,
                    'override_custom_cancel_page' => null,
                    'tax_amount' => 0.0,
                    'merchant_defined_data5' => 'Discount amount: 0',
                    'merchant_defined_data6' => 0,
                    'partner_solution_id' => 'T54H9OLO',
                    'auth_indicator' => null,
                    'bill_to_forename' => null,
                    'bill_to_surname' => null,
                    'bill_to_email' => null,
                    'bill_to_phone' => null,
                    'bill_to_address_line1' => null,
                    'bill_to_address_city' => null,
                    'bill_to_address_postal_code' => null,
                    'bill_to_address_state' => null,
                    'bill_to_address_country' => null,
                    'bill_address1' => null,
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
                    'line_item_count' => 0,
                    'customer_email' => null,
                    'customer_lastname' => null
                ];
                break;
            case 'testBuildDecisionManagerFields':
                $data = [
                    'merchant_defined_data1' => 0,
                    'merchant_defined_data6' => 0,
                    'merchant_defined_data20' => null,
                    'merchant_defined_data21' => 0,
                    'merchant_defined_data22' => '',
                    'merchant_defined_data23' => 'web',
                ];
                break;
        }
        return $data;
    }
}
