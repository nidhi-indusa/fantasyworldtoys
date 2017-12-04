<?php

namespace CyberSource\PayPal\Model\Express;

use CyberSource\PayPal\Helper\RequestDataBuilder;
use CyberSource\PayPal\Model\Config;
use CyberSource\PayPal\Model\Payment;
use CyberSource\PayPal\Service\CyberSourcePayPalSoapAPI;
use Magento\Customer\Api\Data\CustomerInterface as CustomerDataObject;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\DataObject;
use Magento\Paypal\Model\Config as PaypalConfig;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Wrapper that performs Paypal Express and Checkout communication
 * Use current Paypal Express method instance
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Checkout
{
    /**
     * Cache ID prefix for "pal" lookup
     * @var string
     */
    const PAL_CACHE_ID = 'paypal_express_checkout_pal';

    /**
     * Keys for passthrough variables in sales/quote_payment and sales/order_payment
     * Uses additional_information as storage
     */
    const PAYMENT_INFO_TRANSPORT_TOKEN    = 'paypal_express_checkout_token';
    const PAYMENT_INFO_TRANSPORT_SHIPPING_OVERRIDDEN = 'paypal_express_checkout_shipping_overridden';
    const PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD = 'paypal_express_checkout_shipping_method';
    const PAYMENT_INFO_TRANSPORT_PAYER_ID = 'paypal_express_checkout_payer_id';
    const PAYMENT_INFO_TRANSPORT_REDIRECT = 'paypal_express_checkout_redirect_required';
    const PAYMENT_INFO_TRANSPORT_BILLING_AGREEMENT = 'paypal_ec_create_ba';

    /**
     * Flag which says that was used PayPal Express Checkout button for checkout
     * Uses additional_information as storage
     * @var string
     */
    const PAYMENT_INFO_BUTTON = 'button';

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * Config instance
     *
     * @var PaypalConfig
     */
    protected $_config;

    /**
     * API instance
     *
     * @var CyberSourcePayPalSoapAPI
     */
    protected $_api;

    /**
     * Payment method type
     *
     * @var string
     */
    protected $_methodType = Config::CODE;

    /**
     * State helper variable
     *
     * @var string
     */
    protected $_redirectUrl = '';

    /**
     * State helper variable
     *
     * @var string
     */
    protected $_pendingPaymentMessage = '';

    /**
     * State helper variable
     *
     * @var string
     */
    protected $_checkoutRedirectUrl = '';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Redirect urls supposed to be set to support giropay
     *
     * @var array
     */
    protected $_giropayUrls = [];

    /**
     * Create Billing Agreement flag
     *
     * @var bool
     */
    protected $_isBARequested = false;

    /**
     * Flag for Bill Me Later mode
     *
     * @var bool
     */
    protected $_isBml = false;

    /**
     * Customer ID
     *
     * @var int
     */
    protected $_customerId;

    /**
     * Billing agreement that might be created during order placing
     *
     * @var \Magento\Paypal\Model\Billing\Agreement
     */
    protected $_billingAgreement;

    /**
     * Order
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \Magento\Framework\App\Cache\Type\Config
     */
    protected $_configCacheType;

    /**
     * Checkout data
     *
     * @var \Magento\Checkout\Helper\Data
     */
    protected $_checkoutData;

    /**
     * Tax data
     *
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxData;

    /**
     * Customer data
     *
     * @var \Magento\Customer\Model\Url
     */
    protected $_customerUrl;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \CyberSource\PayPal\Model\Info
     */
    protected $_paypalInfo;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_coreUrl;

    /**
     * @var \Magento\Paypal\Model\CartFactory
     */
    protected $_cartFactory;

    /**
     * @var \Magento\Checkout\Model\Type\OnepageFactory
     */
    protected $_checkoutOnepageFactory;

    /**
     * @var \Magento\Paypal\Model\Billing\AgreementFactory
     */
    protected $_agreementFactory;

    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $_objectCopyService;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Customer\Model\AccountManagement
     */
    protected $_accountManagement;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var RequestDataBuilder
     */
    private $dataBuilder;

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Checkout\Helper\Data $checkoutData
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \CyberSource\PayPal\Model\Info $paypalInfo
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $coreUrl
     * @param \Magento\Paypal\Model\CartFactory $cartFactory
     * @param \Magento\Checkout\Model\Type\OnepageFactory $onepageFactory
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Magento\Paypal\Model\Billing\AgreementFactory $agreementFactory
     * @param \Magento\Paypal\Model\Api\Type\Factory $apiTypeFactory
     * @param DataObject\Copy $objectCopyService
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param AccountManagement $accountManagement
     * @param OrderSender $orderSender
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     * @param CyberSourcePayPalSoapAPI $api
     * @param RequestDataBuilder $dataBuilder
     * @param Config $gatewayConfig
     * @param array $params
     * @throws \Exception
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \CyberSource\PayPal\Model\Info $paypalInfo,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $coreUrl,
        \Magento\Paypal\Model\CartFactory $cartFactory,
        \Magento\Checkout\Model\Type\OnepageFactory $onepageFactory,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Paypal\Model\Billing\AgreementFactory $agreementFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        AccountManagement $accountManagement,
        OrderSender $orderSender,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        CyberSourcePayPalSoapAPI $api,
        RequestDataBuilder $dataBuilder,
        Config $gatewayConfig,
        $params = []
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->_customerUrl = $customerUrl;
        $this->_taxData = $taxData;
        $this->_checkoutData = $checkoutData;
        $this->_configCacheType = $configCacheType;
        $this->_logger = $logger;
        $this->_localeResolver = $localeResolver;
        $this->_paypalInfo = $paypalInfo;
        $this->_storeManager = $storeManager;
        $this->_coreUrl = $coreUrl;
        $this->_cartFactory = $cartFactory;
        $this->_checkoutOnepageFactory = $onepageFactory;
        $this->_agreementFactory = $agreementFactory;
        $this->_objectCopyService = $objectCopyService;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerRepository = $customerRepository;
        $this->_encryptor = $encryptor;
        $this->_messageManager = $messageManager;
        $this->orderSender = $orderSender;
        $this->_accountManagement = $accountManagement;
        $this->quoteRepository = $quoteRepository;
        $this->dataBuilder = $dataBuilder;
        $this->totalsCollector = $totalsCollector;
        $this->_api = $api;
        $this->gatewayConfig = $gatewayConfig;

        $this->_customerSession = isset($params['session'])
            && $params['session'] instanceof \Magento\Customer\Model\Session ? $params['session'] : $customerSession;

        if (isset($params['config']) && $params['config'] instanceof PaypalConfig) {
            $this->_config = $params['config'];
        } else {
            throw new \Exception('Config instance is required.');
        }

        if (isset($params['quote']) && $params['quote'] instanceof \Magento\Quote\Model\Quote) {
            $this->_quote = $params['quote'];
        } else {
            throw new \Exception('Quote instance is required.');
        }
    }

    /**
     * Setter for customer
     *
     * @param CustomerDataObject $customerData
     * @return $this
     */
    public function setCustomerData(CustomerDataObject $customerData)
    {
        $this->_quote->assignCustomer($customerData);
        $this->_customerId = $customerData->getId();
        return $this;
    }

    /**
     * Setter for customer with billing and shipping address changing ability
     *
     * @param CustomerDataObject $customerData
     * @param Address|null $billingAddress
     * @param Address|null $shippingAddress
     * @return $this
     */
    public function setCustomerWithAddressChange(
        CustomerDataObject $customerData,
        $billingAddress = null,
        $shippingAddress = null
    ) {
        $this->_quote->assignCustomerWithAddressChange($customerData, $billingAddress, $shippingAddress);
        $this->_customerId = $customerData->getId();
        return $this;
    }

    /**
     * Reserve order ID for specified quote and start checkout on PayPal
     *
     * @param string $returnUrl
     * @param string $cancelUrl
     * @param bool|null $button
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function start($returnUrl, $cancelUrl, $button = null)
    {
        $this->_quote->collectTotals();

        if (!$this->_quote->getGrandTotal()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'PayPal can\'t process orders with a zero balance due. '
                    . 'To finish your purchase, please go through the standard checkout process.'
                )
            );
        }

        $this->_quote->reserveOrderId();
        $this->quoteRepository->save($this->_quote);

        $request = $this->dataBuilder->buildSetService($this->_quote, $returnUrl, $cancelUrl);
        $response = $this->_api->setService($request);

        $this->_checkoutSession->setSetServiceResponse($response);

        $token = $response['paypalToken'];
        $this->_setRedirectUrl($button, $token);

        $payment = $this->_quote->getPayment();

        // Set flag that we came from Express Checkout button
        if (!empty($button)) {
            $payment->setAdditionalInformation(self::PAYMENT_INFO_BUTTON, 1);
        } elseif ($payment->hasAdditionalInformation(self::PAYMENT_INFO_BUTTON)) {
            $payment->unsAdditionalInformation(self::PAYMENT_INFO_BUTTON);
        }
        $payment->save();

        return $token;
    }

    /**
     * Check whether system can skip order review page before placing order
     *
     * @return bool
     */
    public function canSkipOrderReviewStep()
    {
        $isOnepageCheckout = !$this->_quote->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_BUTTON);
        return $this->_config->isOrderReviewStepDisabled() && $isOnepageCheckout;
    }

    /**
     * Update quote when returned from PayPal
     * rewrite billing address by paypal
     * save old billing address for new customer
     * export shipping address in case address absence
     *
     * @param string $token
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function returnFromPaypal($token)
    {
        $setServiceResponse = $this->_checkoutSession->getSetServiceResponse();
        $request = $this->dataBuilder->buildGetDetailsService($setServiceResponse);
        $getDetailsResponse = $this->_api->getDetailsService($request);
        $this->_checkoutSession->setGetDetailsResponse($getDetailsResponse);

        $quote = $this->_quote;

        $this->ignoreAddressValidation();

        // import shipping address
        $exportedShippingAddress = $getDetailsResponse['shippingAddress'];
        if (!$quote->getIsVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            if ($shippingAddress) {
                if ($exportedShippingAddress
                    && $quote->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_BUTTON) == 1
                ) {
                    $this->_setExportedAddressData($shippingAddress, $exportedShippingAddress);
                    // CyberSource doesn't provide detailed shipping info: prefix, middlename, suffix
                    $shippingAddress->setPrefix(null);
                    $shippingAddress->setCollectShippingRates(true);
                    $shippingAddress->setSameAsBilling(0);
                }

                // import shipping method
                $code = '';
                $quote->getPayment()->setAdditionalInformation(
                    self::PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD,
                    $code
                );
            }
        }

        // import billing address
        $portBillingFromShipping = $quote->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_BUTTON) == 1
            && !$quote->isVirtual();
        if ($portBillingFromShipping) {
            $billingAddress = clone $shippingAddress;
            $billingAddress->unsAddressId()->unsAddressType()->setCustomerAddressId(null);
            $data = $billingAddress->getData();
            $data['save_in_address_book'] = 0;
            $quote->getBillingAddress()->addData($data);
            $quote->getShippingAddress()->setSameAsBilling(1);
        } else {
            $billingAddress = $quote->getBillingAddress();
        }
        $exportedBillingAddress = $getDetailsResponse['shippingAddress'];

        $this->_setExportedAddressData($billingAddress, $exportedBillingAddress);
        $billingAddress->setCustomerNote($exportedBillingAddress->getData('note'));
        $quote->setBillingAddress($billingAddress);
        $quote->setCheckoutMethod($this->getCheckoutMethod());

        // import payment info
        $payment = $quote->getPayment();
        $payment->setMethod($this->_methodType);
        $payment->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_PAYER_ID, $getDetailsResponse['paypalPayerId'])
            ->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_TOKEN, $token);
        $quote->collectTotals();
        $this->quoteRepository->save($quote);
    }

    /**
     * Check whether order review has enough data to initialize
     *
     * @param string|null $token
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function prepareOrderReview($token = null)
    {
        $payment = $this->_quote->getPayment();
        if (!$payment || !$payment->getAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_PAYER_ID)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('A payer is not identified.'));
        }
        $this->_quote->setMayEditShippingAddress(
            1 != $this->_quote->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_SHIPPING_OVERRIDDEN)
        );
        $this->_quote->setMayEditShippingMethod(
            '' == $this->_quote->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD)
        );
        $this->ignoreAddressValidation();
        $this->_quote->collectTotals();
        $this->quoteRepository->save($this->_quote);
    }

    /**
     * Set shipping method to quote, if needed
     *
     * @param string $methodCode
     * @return void
     */
    public function updateShippingMethod($methodCode)
    {
        $shippingAddress = $this->_quote->getShippingAddress();
        if (!$this->_quote->getIsVirtual() && $shippingAddress) {
            if ($methodCode != $shippingAddress->getShippingMethod()) {
                $this->ignoreAddressValidation();
                $shippingAddress->setShippingMethod($methodCode)->setCollectShippingRates(true);
                $cartExtension = $this->_quote->getExtensionAttributes();
                if ($cartExtension && $cartExtension->getShippingAssignments()) {
                    $cartExtension->getShippingAssignments()[0]
                        ->getShipping()
                        ->setMethod($methodCode);
                }
                $this->_quote->collectTotals();
                $this->quoteRepository->save($this->_quote);
            }
        }
    }

    /**
     * Place the order when customer returned from PayPal until this moment all quote data must be valid.
     *
     * @param string $token
     * @param string|null $shippingMethodCode
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function place($token, $shippingMethodCode = null)
    {
        if ($shippingMethodCode) {
            $this->updateShippingMethod($shippingMethodCode);
        }

        if ($this->getCheckoutMethod() == \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {
            $this->prepareGuestQuote();
        }

        $this->ignoreAddressValidation();
        $this->_quote->collectTotals();

        $getDetailsResponse = $this->_checkoutSession->getGetDetailsResponse();

        $orderSetupRequest = $this->dataBuilder->buildOrderSetupService($getDetailsResponse, $this->_quote);
        $orderSetupResponse = $this->_getApi()->orderSetupService($orderSetupRequest);

        $authorizationRequest = $this->dataBuilder->buildAuthorizationService($orderSetupResponse, $this->_quote, $getDetailsResponse['paypalCustomerEmail']);
        $authorizationResponse = $this->_getApi()->authorizationService($authorizationRequest);

        $paymentStatus = $this->handlePaymentStatus($authorizationResponse);

        $this->_quote->getPayment()->setMethod(Payment::CODE);

        $this->_quote->getPayment()->setAdditionalInformation('payment_status', $paymentStatus);
        $this->_quote->getPayment()->setAdditionalInformation('pending_reason', $authorizationResponse->reasonCode);
        $this->_quote->getPayment()->setAdditionalInformation('authorize', serialize($authorizationResponse));
        $this->_quote->getPayment()->setAdditionalInformation('authorized_items', serialize($authorizationRequest->item));

        if ($authorizationResponse->reasonCode == 480) {
            $this->_quote->getPayment()->setAdditionalInformation('is_fraud_detected', true);
        }

        $this->quoteRepository->save($this->_quote);

        $order = $this->quoteManagement->submit($this->_quote);

        if (!$order) {
            return;
        }

        // commence redirecting to finish payment, if paypal requires it
        if ($order->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_REDIRECT)) {
            $this->_redirectUrl = $this->_config->getExpressCheckoutCompleteUrl($token);
        }

        switch ($order->getState()) {
            // even after placement paypal can disallow to authorize/capture, but will wait until bank transfers money
            case \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT:
                // TODO
                break;
                // regular placement, when everything is ok
            case \Magento\Sales\Model\Order::STATE_PROCESSING:
            case \Magento\Sales\Model\Order::STATE_COMPLETE:
            case \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW:
                $this->orderSender->send($order);
                $this->_checkoutSession->start();
                break;
            default:
                break;
        }
        $this->_order = $order;
    }

    private function handlePaymentStatus($authorizationResponse)
    {
        if ($this->gatewayConfig->getPaymentAction() == \CyberSource\PayPal\Model\Payment::ACTION_AUTHORIZE) {
            $paymentStatus = Order::STATE_PENDING_PAYMENT;
        } else {
            $paymentStatus = Order::STATE_PROCESSING;
        }

        switch ($authorizationResponse->decision) {
            case 'DECLINE':
            case 'ERROR':
            case 'REJECT':
                $paymentStatus = Order::STATE_CANCELED;
                break;

            case 'REVIEW':
                $paymentStatus = Order::STATE_PAYMENT_REVIEW;
                $this->_quote->getPayment()->setAdditionalInformation('is_fraud_detected', true);
                break;
        }

        return $paymentStatus;
    }

    /**
     * Make sure addresses will be saved without validation errors
     *
     * @return void
     */
    private function ignoreAddressValidation()
    {
        $this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$this->_quote->getIsVirtual()) {
            $this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);
            if (!$this->_config->getValue('requireBillingAddress')
                && !$this->_quote->getBillingAddress()->getEmail()
            ) {
                $this->_quote->getBillingAddress()->setSameAsBilling(1);
            }
        }
    }

    /**
     * Determine whether redirect somewhere specifically is required
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->_redirectUrl;
    }

    /**
     * Return order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Get checkout method
     *
     * @return string
     */
    public function getCheckoutMethod()
    {
        if ($this->getCustomerSession()->isLoggedIn()) {
            return \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
        }
        if (!$this->_quote->getCheckoutMethod()) {
            if ($this->_checkoutData->isAllowedGuestCheckout($this->_quote)) {
                $this->_quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
            } else {
                $this->_quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
            }
        }
        return $this->_quote->getCheckoutMethod();
    }

    /**
     * Sets address data from exported address
     *
     * @param Address $address
     * @param array $exportedAddress
     * @return void
     */
    protected function _setExportedAddressData($address, $exportedAddress)
    {
        // Exported data is more priority if we came from Express Checkout button
        $isButton = (bool)$this->_quote->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_BUTTON);
        if (!$isButton) {
            return;
        }

        foreach ($exportedAddress->getExportedKeys() as $key) {
            $data = $exportedAddress->getData($key);
            if (!empty($data)) {
                $address->setDataUsingMethod($key, $data);
            }
        }
    }

    /**
     * @return CyberSourcePayPalSoapAPI
     */
    protected function _getApi()
    {
        return $this->_api;
    }

    /**
     * Create payment redirect url
     * @param bool|null $button
     * @param string $token
     * @return void
     */
    protected function _setRedirectUrl($button, $token)
    {
        $this->_redirectUrl = ($button && !$this->_taxData->getConfig()->priceIncludesTax())
            ? $this->gatewayConfig->getExpressCheckoutStartUrl($token)
            : $this->gatewayConfig->getPayPalBasicStartUrl($token);
    }

    /**
     * Get customer session object
     *
     * @return \Magento\Customer\Model\Session
     */
    public function getCustomerSession()
    {
        return $this->_customerSession;
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @return $this
     */
    protected function prepareGuestQuote()
    {
        $quote = $this->_quote;
        $quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
        return $this;
    }
}
