<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Helper;

use CyberSource\Core\Model\Source\AuthIndicator;
use CyberSource\SecureAcceptance\Model\Config;
use CyberSource\Core\Helper\AbstractDataBuilder;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\GiftMessage\Model\Message;
use Magento\Framework\Exception\LocalizedException;

class RequestDataBuilder extends AbstractDataBuilder
{
    const TAX_AMOUNT = 'merchant_defined_data6';
    const USE_IFRAME = 'merchant_defined_data11';
    const REQ_USE_IFRAME = 'req_merchant_defined_data11';
    const PAY_URL = 'pay_url';
    const PAY_TEST_URL = 'pay_test_url';

    const PARTNER_SOLUTION_ID = 'T54H9OLO';
    
    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var string
     */
    private $transactionType = 'sale,create_payment_token';

    /**
     * @var string
     */
    private $requestUrl = '';

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $giftMessage;

    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;

    /**
     *
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    private $customerModel;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var array
     */
    private $requestUrls = [
        self::PAY_URL => 'https://secureacceptance.cybersource.com/pay',
        self::PAY_TEST_URL => 'https://testsecureacceptance.cybersource.com/pay'
    ];
    
    /**
     * RequestDataBuilder constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param SessionManagerInterface $customerSession
     * @param CollectionFactory $orderCollectionFactory
     * @param SessionManagerInterface $checkoutSession
     * @param Resolver $resolver
     * @param CheckoutHelper $checkoutHelper
     * @param Config $gatewayConfig
     * @param Message $message
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\Customer\Model\Customer $customerModel
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        SessionManagerInterface $customerSession,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        Resolver $resolver,
        CheckoutHelper $checkoutHelper,
        Config $gatewayConfig,
        Message $message,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Checkout\Helper\Cart $helperCart,
        \Magento\Checkout\Model\Session $cs
    ) {
        $this->resolver = $resolver;
        $this->gatewayConfig = $gatewayConfig;
        $this->locale = str_replace('_', '-', strtolower($this->resolver->getLocale()));
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->giftMessage = $message;
        $this->auth = $auth;
        $this->customerModel = $customerModel;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->helperCart = $helperCart;
        $this->cs = $cs;
        parent::__construct($context, $customerSession, $checkoutSession, $checkoutHelper);
    }
    
    
    /**
     * @return array
     */
    public function buildSilentRequestData($guestEmail = null, $isTokenPay = false, $token = null)
    {
        $this->setTransactionType();

        $quote = $this->checkoutSession->getQuote();
        if ($quote == null) {
            throw new LocalizedException(
                __('Sorry we can\'t place an order. Some error happens during order placement.')
            );
        }
        $quote->reserveOrderId();
        $quote->save();
        $this->checkoutSession->replaceQuote($quote);

        $amount = $quote->getGrandTotal();

        $billingAddress = $quote->getBillingAddress();
        
        $shippingAddress = $quote->getShippingAddress();

        $params = [
            'access_key' => $this->scopeConfig->getValue("payment/chcybersource/sop_access_key", \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'profile_id' => $this->scopeConfig->getValue("payment/chcybersource/sop_profile_id", \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'transaction_uuid' => uniqid(),
            'signed_field_names' => $this->getSignedFields(false, $this->gatewayConfig->isSilent()),
            'unsigned_field_names' => ($isTokenPay) ? 'payment_token' : 'card_type,card_number,card_expiry_date',
            'signed_date_time' => gmdate("Y-m-d\\TH:i:s\\Z"),
            'locale' => $this->locale,
            //weird but nothing works for SOP with create_payment_token
            'transaction_type' => (!empty($guestEmail) || $isTokenPay) ? str_replace(',create_payment_token', '', $this->transactionType) : $this->transactionType,
            'reference_number' => $quote->getReservedOrderId(),
            'amount' => $this->formatAmount($amount),
            'tax_amount' => $this->formatAmount($shippingAddress->getTaxAmount()),
            'currency' => $quote->getCurrency()->getData('store_currency_code'),
            'payment_method' => 'card',
            'partner_solution_id' => self::PARTNER_SOLUTION_ID,
            'developer_id' => $this->scopeConfig->getValue(
                "payment/chcybersource/developer_id",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            //payer auth fields
            'payer_auth_enroll_service_run' => 'true'
        ];

        $params['signed_field_names'] .= ',tax_amount';
        $params['unsigned_field_names'] .= ',developer_id';
        
        $customerEmail = (!empty(trim($guestEmail)) && $guestEmail != 'null') ? trim($guestEmail) : $quote->getCustomerEmail();
        $params = array_merge($params, $this->buildBillingAddress($billingAddress, $customerEmail, true));
        //(!empty($guestEmail) ? $guestEmail :
        $params = array_merge($params, $this->buildShippingAddress($shippingAddress, $customerEmail, true));
        $params = array_merge($params, $this->buildDecisionManagerFields($quote, $billingAddress));
        $params = array_merge($params, $this->buildOrderItems($quote));
        
        if ($isTokenPay && !empty($token)) {
            $params['payment_token'] = $token;
            $params['signed_field_names'] .= ',payment_token';
        }
        
        $fingerprintId = $this->checkoutSession->getData('fingerprint_id');
        if (!empty($fingerprintId)) {
            $params['device_fingerprint_id'] = $fingerprintId;
            $params['signed_field_names'] .= ',device_fingerprint_id';
        }
        $params['unsigned_field_names'] .= ','.$this->getUnsignedFields($params);
        foreach ($params as &$value) {
             $value = str_replace("\n", '', trim($value));
        }
        $params['signature'] = $this->sign($params, $this->scopeConfig->getValue("payment/chcybersource/sop_secret_key", \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        $this->_logger->info('Silent params: '.print_r($params, 1));
        return $params;
    }
    
    /**
     * @param bool $useIFrame
     * @param null $guestEmail
     * @return array
     */
    public function buildRequestData($useIFrame = false, $guestEmail = null)
    {
        $this->setTransactionType();
        $quote = $this->checkoutSession->getQuote();

        $this->_logger->info("build request quote id = ".$quote->getId());
        $this->_logger->info("build request reserved order id = ".$quote->getReservedOrderId());
        $quote->reserveOrderId();
        $quote->collectTotals()->save();
        $this->_logger->info("build request reserved order id = ".$quote->getReservedOrderId());
        $this->_logger->info("Cart id = ".$this->helperCart->getCart()->getId());
        $this->_logger->info("Customer email = ".$quote->getCustomerEmail());
        $this->_logger->info("Customer email = ".$this->cs->getQuote()->getCustomerEmail());
//        $this->checkoutSession->replaceQuote($quote);
        $billingAddress = $quote->getBillingAddress();
        if ($this->getCheckoutMethod($quote) === Onepage::METHOD_GUEST) {
            $billingAddress->setEmail($guestEmail);
            $this->prepareGuestQuote($quote);
        }
        $params = $this->buildBasePaymentData($quote, $billingAddress);
        $this->_logger->info('request params before = '.print_r($params, 1));
        $params = array_merge($params, $this->buildOrderItems($quote));
        $iFrameEnabled = $this->gatewayConfig->getUseIframe();
        if ($this->_request->getParam('token')) {
            $params['transaction_type'] = ($this->gatewayConfig->getPaymentAction() == 'authorize') ? 'authorization': 'sale';
            $params['payment_token'] = $this->_request->getParam('token');
            $params['signed_field_names'] = $params['signed_field_names'] . ',payment_token';
            $this->setTransactionType();
            $this->transactionType = $params['transaction_type'];
            $params['request_url'] = $this->requestUrl;
        }
        $params['customer_email'] = $quote->getCustomerEmail();
        $params['customer_lastname'] = $quote->getCustomerLastname();
        $params['unsigned_field_names'] = $this->getUnsignedFields($params);
        $params['developer_id'] = $this->scopeConfig->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $params['unsigned_field_names'] .= ',developer_id';
        $params['customer_cookies_accepted'] = 'false';
        $params['signed_field_names'] .= ',customer_cookies_accepted';
        $params['signature'] = $this->sign($params, $this->gatewayConfig->getSecretKey());
        if ($useIFrame) {
            $params[self::USE_IFRAME] = $useIFrame;
        }
        if ($iFrameEnabled) {
            $params['use_iframe'] = 1;
        }
        $this->_logger->info('request params = '.print_r($params, 1));
        return $params;
    }

    /**
     * @param Quote $quote
     * @param Quote\Address $billingAddress
     * @return array
     */
    private function buildBasePaymentData(Quote $quote, Quote\Address $billingAddress)
    {
        $shippingAddress = $quote->getShippingAddress();

        $params = [
            'access_key' => $this->gatewayConfig->getAccessKey(),
            'profile_id' => $this->gatewayConfig->getProfileId(),
            'ignore_avs' => $this->gatewayConfig->getIgnoreAvs(),
            'ignore_cvn' => $this->gatewayConfig->getIgnoreCvn(),
            'transaction_uuid' => uniqid(),
            'payment_method' => 'card',
            'card_number' => '',
            'signed_field_names' => $this->getSignedFields(false, $this->gatewayConfig->isSilent()),
            'unsigned_field_names' => '',
            'signed_date_time' => gmdate("Y-m-d\\TH:i:s\\Z"),
            'locale' => $this->locale,
            'transaction_type' => $this->transactionType,
            'reference_number' => $quote->getReservedOrderId(),
            'amount' => $this->formatAmount($quote->getGrandTotal()),
            'currency' => $quote->getCurrency()->getData('store_currency_code'),
            'request_url' => $this->requestUrl,
            'override_custom_receipt_page' => $this->urlBuilder->getUrl('cybersource/index/receipt'),
            'override_custom_cancel_page' => $this->urlBuilder->getUrl('cybersource/index/cancel'),
            'tax_amount' => $this->formatAmount($shippingAddress->getTaxAmount()),
            'merchant_defined_data5' => __('Discount amount: ') .
                (float) $quote->getShippingAddress()->getData('discount_amount'),
            self::TAX_AMOUNT => __('Tax amount: ').(float) $shippingAddress->getTaxAmount(),
            'partner_solution_id' => self::PARTNER_SOLUTION_ID
        ];

        if ($this->gatewayConfig->getAuthIndicator() != AuthIndicator::UNDEFINED) {
            $params['auth_indicator'] = $this->gatewayConfig->getAuthIndicator();
        }
        $params = array_merge($params, $this->buildBillingAddress($billingAddress, $quote->getCustomerEmail()));
        $params = array_merge($params, $this->buildShippingAddress($shippingAddress, $quote->getCustomerEmail()));
        $params = array_merge($params, $this->buildDecisionManagerFields($quote, $billingAddress));
        return $params;
    }

    /**
     *
     * @param Quote $quote
     * @param Quote\Address $billingAddress
     * @return array
     */
    public function buildDecisionManagerFields(Quote $quote, Quote\Address $billingAddress)
    {
        $data = [];
        $data['merchant_defined_data1'] = (int)$this->customerSession->isLoggedIn();// Registered or Guest Account
        
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerModel->load($this->customerSession->getCustomerId());
            $data['merchant_defined_data2'] = $customer->getData('created_at'); // Account Creation Date

            $orders = $this->orderCollectionFactory->create()
                ->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())
                ->setOrder('created_at', 'desc');

            $data['merchant_defined_data3'] = count($orders); // Purchase History Count

            if ($orders->getSize() > 0) {
                $lastOrder = $orders->getFirstItem();
                $data['merchant_defined_data4'] = $lastOrder->getData('created_at'); // Last Order Date
            }

            $data['merchant_defined_data5'] = round((time() - strtotime($customer->getData('created_at'))) / (3600*24));// Member Account Age (Days)
        }

        $orders = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_email', $quote->getCustomerEmail());

        $data['merchant_defined_data6'] = (int)(count($orders) > 0); // Repeat Customer
        $data['merchant_defined_data20'] = $quote->getCouponCode(); //Coupon Code
        $data['merchant_defined_data21'] = ($quote->getSubtotal() - $quote->getSubtotalWithDiscount()); // Discount

        $message = $this->giftMessage->load($quote->getGiftMessageId());
        $data['merchant_defined_data22'] = ($message) ? $message->getMessage() : ''; // Gift Message
        $data['merchant_defined_data23'] = ($this->auth->isLoggedIn()) ? 'call center' : 'web'; //order source

        $data['consumer_id'] = $this->customerSession->getCustomerId();
        $data['customer_ip_address'] = $this->_remoteAddress->getRemoteAddress();
        
        return $data;
    }

    /**
     * @param Quote\Address $billingAddress
     * @param $customerEmail
     * @param bool $isSilent
     * @return array
     */
    private function buildBillingAddress(Quote\Address $billingAddress, $customerEmail, $isSilent = false)
    {
        
        $data = [
            'bill_to_forename' => $billingAddress->getData('firstname'),
            'bill_to_surname' => $billingAddress->getData('lastname'),
            'bill_to_email' => $customerEmail,
            'bill_to_phone' => (!empty($billingAddress->getData('telephone')) ? $billingAddress->getData('telephone') : ''),
            'bill_to_address_line1' => $billingAddress->getStreetLine(1),
            'bill_to_address_line2' => $billingAddress->getStreetLine(2),
            'bill_to_address_city' => $billingAddress->getData('city'),
            'bill_to_address_postal_code' => $billingAddress->getData('postcode'),
            'bill_to_address_state' => $billingAddress->getRegionCode(),
            'bill_to_address_country' => $billingAddress->getData('country_id'),
        ];
        
        if (!$isSilent) {
            $data['bill_address1'] = $billingAddress->getStreetLine(1);
            $data['bill_address2'] = $billingAddress->getStreetLine(2);
            $data['bill_city'] = $billingAddress->getData('city');
            $data['bill_country'] = $billingAddress->getData('country_id');
        }
        
        return $data;
    }

    /**
     * @param Quote\Address $shippingAddress
     * @param $customerEmail
     * @param bool $isSilent
     * @return array
     */
    private function buildShippingAddress(Quote\Address $shippingAddress, $customerEmail, $isSilent = false)
    {
        $data = [
            'ship_to_forename' => $shippingAddress->getData('firstname'),
            'ship_to_surname' => $shippingAddress->getData('lastname'),
            'ship_to_email' => $customerEmail,
            'ship_to_address_line1' => $shippingAddress->getStreetLine(1),
            'ship_to_address_line2' => $shippingAddress->getStreetLine(2),
            'ship_to_address_city' => $shippingAddress->getData('city'),
            'ship_to_address_postal_code' => $shippingAddress->getData('postcode'),
            'ship_to_address_state' => $shippingAddress->getRegionCode(),
            'ship_to_address_country' => $shippingAddress->getData('country_id')
        ];
        
        if ($isSilent) {
            $data['ship_to_state'] = $shippingAddress->getRegionCode();
            $data['ship_to_country'] = $shippingAddress->getData('country_id');
        }
        return $data;
    }

    /**
     * @param Quote $quote
     * @return mixed
     */
    private function buildOrderItems(Quote $quote)
    {
        $orderItems = $quote->getAllItems();

        $i = 0;
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($orderItems as $item) {
            $this->_logger->info("order item = ".$item->getSku());
            $price = (float)$item->getPrice();
            if (!empty($price)) {
                $qty = $item->getQty();
                if (empty($qty)) {
                    $qty = 1;
                }

                $amount = ($item->getPrice() - ($item->getDiscountAmount() / $qty)) * $qty;

                $params['item_'.$i.'_name'] = $item->getName();
                $params['item_'.$i.'_sku'] = $item->getSku();
                $params['item_'.$i.'_quantity'] = $qty;
                $params['item_'.$i.'_unit_price'] = $this->formatAmount($amount);
                $params['item_'.$i.'_tax_amount'] = $this->formatAmount($item->getTaxAmount());
                $i++;
            }
        }
        $params['line_item_count'] = $i;
        return $params;
    }

    /**
     * @return void
     */
    public function setTransactionType()
    {
        $paymentAction = $this->gatewayConfig->getPaymentAction();
        $isTestMode = $this->gatewayConfig->isTestMode();

        $this->requestUrl = $this->requestUrls[self::PAY_URL];

        if ($isTestMode) {
            $this->requestUrl = $this->requestUrls[self::PAY_TEST_URL];
        }

        if ('authorize' === $paymentAction) {
            $this->transactionType = 'authorization,create_payment_token';
        }
    }
    
    public function getUseIframe()
    {
        return $this->gatewayConfig->getUseIframe();
    }
}
