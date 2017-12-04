<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Helper;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Data extends AbstractDataBuilder
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $auth;
    
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry = null;

    /**
     * Order
     *
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    private $order = null;

    /**
     * Token Collection
     *
     * @var \CyberSource\Core\Model\ResourceModel\Token\Collection
     */
    private $tokenCollection;
    
    /**
     * Url Builder
     *
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Order Factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \CyberSource\Core\Model\Config
     */
    private $gatewayConfig;

    /**
     * @var array
     */
    private $includeAdditionalPaymentKeys = [
        'reasonCode',
        'requestID',
        'request_id',
        'last4',
        'cardType',
        'merchantReferenceCode',
        'merchant_reference_number',
        'total_tax_amount',
        'sa_type',
        'method_name',
        'authorize',
        'capture'
    ];

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param SessionManagerInterface $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Checkout\Helper\Data $data
     * @param \Magento\Backend\Model\Auth\Session $auth
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Sales\Model\AdminOrder\Create $order
     * @param \CyberSource\Core\Model\ResourceModel\Token\Collection $tokenCollection
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \CyberSource\Core\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        SessionManagerInterface $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $data,
        \Magento\Backend\Model\Auth\Session $auth,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Sales\Model\AdminOrder\Create $order,
        \CyberSource\Core\Model\ResourceModel\Token\Collection $tokenCollection,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \CyberSource\Core\Model\Config $config
    ) {

        parent::__construct($context, $customerSession, $checkoutSession, $data);

        $this->auth = $auth;
        $this->orderRepository = $orderRepository;
        $this->coreRegistry = $coreRegistry;
        $this->order = $order;
        $this->tokenCollection = $tokenCollection;
        $this->urlBuilder = $backendUrl;
        $this->orderFactory = $orderFactory;
        $this->gatewayConfig = $config;
    }

    public function getTokens($storeId)
    {
        $this->tokenCollection->addFieldToFilter(
            'customer_id',
            $this->order->getQuote()->getData('customer_id')
        );

        $this->tokenCollection->addFieldToFilter('store_id', $storeId);

        $this->tokenCollection->setOrder('created_date', 'desc');

        $this->tokenCollection->load();

        $tokens = [];

        foreach ($this->tokenCollection as $token) {
            $tokens[] = $token->getData();
        }

        return $tokens;
    }

    public function getGatewayConfig()
    {
        return $this->gatewayConfig;
    }

    /**
     * Return quote for admin order
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->order->getQuote();
    }
    
    /**
     * Return URL for admin area
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    protected function _getUrl($route, $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }

    /**
     * Retrieve place order url in admin
     *
     * @return  string
     */
    public function getPlaceOrderAdminUrl()
    {
        return $this->_getUrl('cybersourceadmin/order_cybersource/payment', []);
    }
    
    /**
     * Retrieve place order url
     *
     * @param array $params
     * @return  string
     */
    public function getSuccessOrderUrl($params)
    {
        $param = [];
        $route = 'sales/order/view';
        $order = $this->orderFactory->create()->loadByIncrementId($params['x_invoice_num']);
        $param['order_id'] = $order->getId();
        return $this->_getUrl($route, $param);
    }

    /**
     * @param string $paymentGateway
     * @return boolean
     */
    public function isMultipleCapture($paymentGateway)
    {
        
        $pp = [
            'aibms' => true,
            'ae_direct' => false,
            'asia_gateway' => true,
            'atos' => false,
            'barclays' => true,
            'ccs' => true,
            'chase' => true,
            'cielo' => false,
            'comercio' => false,
            'cybersource_latin' => false,
            'cybersource_visanet' => false,
            'fdc_compass' => true,
            'fdc_germany' => false,
            'fdc_nashville_global' => false,
            'fdms_nashville' => false,
            'fdms_south' => false,
            'gpn' => true,
            'hbos' => false,
            'hsbc' => true,
            'ingenico' => false,
            'jcn' => true,
            'litle' => true,
            'lloyds_omnipay' => false,
            'lloydstsb' => true,
            'moneris' => false,
            'omnipay_direct' => true,
            'omnipay_ireland' => true,
            'streamline' => false,
            'tsys' => true,
        ];
        
        return (!empty($pp[$paymentGateway])) ? $pp[$paymentGateway] : false;
    }

    public function getPaymentProcessorName($paymentGateway)
    {
        $pp = [
            'aibms' => 'AIBMS',
            'ae_direct' => 'American Express Direct',
            'asia_gateway' => 'Asia, Middle East and Africa Gateway',
            'atos' => 'Atos',
            'barclays' => 'Barclays',
            'ccs' => 'CCS (CAFIS)',
            'chase' => 'Chase Paymentech Solutions',
            'cielo' => 'Cielo',
            'comercio' => 'Comercio Latino',
            'cybersource_latin' => 'CyberSource Latin American Processing',
            'cybersource_visanet' => 'CyberSource through VisaNet',
            'fdc_compass' => 'FDC Compass',
            'fdc_germany' => 'FDC Germany',
            'fdc_nashville_global' => 'FDC Nashville Global',
            'fdms_nashville' => 'FDMS Nashville',
            'fdms_south' => 'FDMS South',
            'gpn' => 'GPN',
            'hbos' => 'HBoS',
            'hsbc' => 'HSBC is the CyberSource name for HSBC U.K',
            'ingenico' => 'Ingenico ePayments was previously called Global Collect',
            'jcn' => 'JCN Gateway',
            'litle' => 'Litle',
            'lloyds_omnipay' => 'Lloyds-OmniPay',
            'lloydstsb' => 'LloydsTSB Cardnet',
            'moneris' => 'Moneris',
            'omnipay_direct' => 'OmniPay Direct',
            'omnipay_ireland' => 'OmniPay-Ireland',
            'streamline' => 'Streamline',
            'tsys' => 'TSYS Acquiring Solutions',
        ];
        
        return (!empty($pp[$paymentGateway])) ? $pp[$paymentGateway] : 'UNKNOWN';
    }

    /**
     * Return only echeck info from CyberSource Response
     *
     * @param $request
     * @return array
     */
    public function getAdditionalData($request)
    {
        $keys = array_keys($request);

        $echeckData = [];
        foreach ($keys as $key) {
            if (in_array($key, $this->includeAdditionalPaymentKeys) ||
                preg_match('/^Tax Amount for/', $key)) {
                switch ($key) {
                    case 'sa_type':
                        $echeckData['Method'] = ($request[$key] == 'web') ? 'Secure Acceptance WEB/Mobile' : 'Secure Acceptance SOP';
                        break;
                    case 'cardType':
                        $echeckData[$key] = $this->getCardType($request[$key]);
                        break;

                    case 'capture':
                        $paypalCaptureResponse = unserialize($request[$key]);
                        foreach ((array)$paypalCaptureResponse->payPalDoCaptureReply as $key => $value) {
                            $echeckData[$key] = $value;
                        }
                        break;

                    case 'authorize':
                        $paypalAuthorizeResponse = unserialize($request[$key]);

                        foreach ((array)$paypalAuthorizeResponse->payPalAuthorizationReply as $key => $value) {
                            $echeckData[$key] = $value;
                        }
                        break;

                    default:
                        $echeckData[$key] = $request[$key];
                }
            }
        }

        return $echeckData;
    }
    
    private function getCardType($code)
    {
        $types = [
            '001' => 'VI',
            '002' => 'MC',
            '003' => 'AE',
            '004' => 'DI',
        ];
        return (!empty($types[$code])) ? $types[$code] : $code;
    }
}
