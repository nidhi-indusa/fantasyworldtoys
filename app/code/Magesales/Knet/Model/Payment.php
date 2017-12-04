<?php
namespace Magesales\Knet\Model;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;

class Payment extends AbstractMethod
{
    const CODE = 'knet';
 	protected $_code = self::CODE;
	
	protected $_formBlockType = 'Magesales\Knet\Block\Form';
	
    const RETURN_CODE_ACCEPTED      = 'Success';
    const RETURN_CODE_TEST_ACCEPTED = 'Success';
    const RETURN_CODE_ERROR         = 'Fail';

    protected $_isGateway = true;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $_canCapturePartial = false;
	protected $_canRefund = true;
	protected $_canRefundInvoicePartial = true;
	protected $_canVoid = true;
	protected $_canUseInternal = true;
	protected $_canUseCheckout = true;
	protected $_canUseForMultishipping = false;
	protected $_canSaveCc = false;
    

    protected $urlBuilder;
	protected $_paymentData = null;
    protected $_moduleList;
    protected $checkoutSession;
    protected $_orderFactory;
	protected $_storeManager;
	protected $logger;
	protected $_region;
	protected $_country;
	
	public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Url $urlBuilder,
		\Magento\Directory\Model\RegionFactory $region,
		\Magento\Directory\Model\CountryFactory $country,
        \Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []
    ) {
		//$this->_paymentData = $paymentData;
		$this->urlBuilder = $urlBuilder;
		$this->_moduleList = $moduleList;
        $this->_scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
		$this->_storeManager = $storeManager;
		$this->_region = $region;
		$this->_country = $country;
		$this->logger = $logger;
		
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,			
            $customAttributeFactory,
			$paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }
	
	/**
     *  Return back URL
     *
     *  @return	  string URL
     */
	public function getReturnURL()
	{
		return $this->urlBuilder->getUrl('checkout/onepage/success', ['_secure' => true]);
	}

	/**
	 *  Return URL for Alipay success response
	 *
	 *  @return	  string URL
	 */
	public function getSuccessURL()
	{
		return $this->urlBuilder->getUrl('knet/payment/success', ['_secure' => true]);
	}

    /**
     *  Return URL for Alipay failure response
     *
     *  @return	  string URL
     */
    public function getErrorURL()
    {
        return $this->urlBuilder->getUrl('knet/payment/error', ['_secure' => true]);
	}

	/**
     * Capture payment
     *
     * @param   Varien_Object $orderPayment
     * @return  Mage_Payment_Model_Abstract
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
            ->setLastTransId($this->getTransactionId());

        return $this;
    }

    public function getKnetUrl()
    {
        return $this->urlBuilder->getUrl('knet/payment/makepayment', ['_secure' => true]);
	}

    /**
     *  Return Order Place Redirect URL
     *
     *  @return	  string Order Redirect URL
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->urlBuilder->getUrl('knet/payment/redirect', ['_secure' => true]);
	}

    /**
     *  Return Standard Checkout Form Fields for request to Alipay
     *
     *  @return	  array Array of hidden form fields
     */
    public function getStandardCheckoutFormFields()
    {
        $session = $this->checkoutSession;
        
        $order = $this->getOrder();
        if (!($order instanceof \Magento\Sales\Model\Order)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Cannot retrieve order object'));
		}
		$orderIncrementId = $session->getLastRealOrderId();
		$region = $this->_region->create();
		$country = $this->_country->create();
		$billingAddress = $order->getBillingAddress();
		$shippingAddress = $order->getShippingAddress();
		if ($shippingAddress)
			$shippingData = $shippingAddress->getData();
		
		$checkout_knet = [];
		$checkout_knet['Amount'] = round($order->getBaseGrandTotal(), 3);
		$checkout_knet['Order_Id'] = $orderIncrementId;
		$checkout_knet['billing_cust_name'] = $billingAddress->getName();
		$checkout_knet['billing_cust_address'] = $billingAddress->getStreet()[0];
		$checkout_knet['billing_cust_state'] = $region->loadByName($billingAddress->getRegion(), $billingAddress->getCountryId())->getCode();
		$checkout_knet['billing_cust_country'] = $country->load($billingAddress->getCountryId())->getName();
		$checkout_knet['billing_cust_tel'] = $billingAddress->getTelephone();
		$checkout_knet['billing_cust_email'] = $order->getEmail();
		if ( $shippingAddress ) {
			$checkout_knet['delivery_cust_name'] = $shippingAddress->getName();
			$checkout_knet['delivery_cust_address'] = $shippingAddress->getStreet()[0];
			$checkout_knet['delivery_cust_state'] = $region->loadByName($shippingAddress->getRegion(), $shippingAddress->getCountryId())->getCode();
			$checkout_knet['delivery_cust_country'] = $country->load($shippingAddress->getCountryId())->getName();
			$checkout_knet['delivery_cust_tel'] = $shippingAddress->getTelephone();
			$checkout_knet['delivery_city'] = $shippingAddress->getCity();
			$checkout_knet['delivery_zip'] = $shippingAddress->getPostcode();
		}
		else {
			$checkout_knet['delivery_cust_name'] = '';
			$checkout_knet['delivery_cust_address'] = '';
			$checkout_knet['delivery_cust_state'] = '';
			$checkout_knet['delivery_cust_country'] = '';
			$checkout_knet['delivery_cust_tel'] = '';
			$checkout_knet['delivery_city'] = '';
			$checkout_knet['delivery_zip'] = '';
		}
		$checkout_knet['Merchant_Param'] = '';
		$checkout_knet['billing_city'] = $billingAddress->getCity();
		$checkout_knet['billing_zip'] = $billingAddress->getPostcode();
		$checkout_knet['billing_cust_notes'] = '';
		
		return $checkout_knet;
    }
    
	public function getInstructions()
    {
        return __('You will be redirected to the Knet website when you place an order.');
    }
    
    /**
     * Order payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canOrder()) {
            throw new LocalizedException(__('The order action is not available.'));
        }

        return $this;
    }
	
	public function getCode()
    {
        if (empty($this->_code)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot retrieve the payment method code.'));
        }
        return $this->_code;
    }
	
	public function getConfig($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }
        
		$path = 'payment/' . $this->getCode() . '/' . $field;
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}