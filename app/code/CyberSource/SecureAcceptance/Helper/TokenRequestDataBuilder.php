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
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Directory\Model\Region;
use Magento\Framework\Session\SessionManagerInterface;

class TokenRequestDataBuilder extends AbstractDataBuilder
{
    const TAX_AMOUNT = 'merchant_defined_data6';
    const USE_IFRAME = 'merchant_defined_data11';

    const PROD_BASE_URL = "https://secureacceptance.cybersource.com/";
    const TEST_BASE_URL = "https://testsecureacceptance.cybersource.com/";
    const TOKEN_CREATE_URI = 'token/create';
    const TOKEN_UPDATE_URI = 'token/update';
    const SAVE_BILLING_ADDRESS = 'merchant_defined_data10';

    const PARTNER_SOLUTION_ID = 'T54H9OLO';
    
    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var string
     */
    private $transactionType = 'create_payment_token';

    /**
     * @var string
     */
    private $requestUrl = self::PROD_BASE_URL;

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var
     */
    private $region;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var array
     */
    private $requestUrls = [
        self::PROD_BASE_URL => 'https://secureacceptance.cybersource.com',
        self::TEST_BASE_URL => 'https://testsecureacceptance.cybersource.com'
    ];

    /**
     * OnSiteDataBuilder constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param SessionManagerInterface $customerSession
     * @param SessionManagerInterface $checkoutSession
     * @param Resolver $resolver
     * @param CheckoutHelper $checkoutHelper
     * @param Config $gatewayConfig
     * @param Region $region
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        SessionManagerInterface $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        Resolver $resolver,
        CheckoutHelper $checkoutHelper,
        Config $gatewayConfig,
        Region $region
    ) {
        $this->resolver = $resolver;
        $this->gatewayConfig = $gatewayConfig;
        $this->locale = str_replace('_', '-', strtolower($this->resolver->getLocale()));
        $this->region = $region;
        $this->urlBuilder = $context->getUrlBuilder();
        parent::__construct($context, $customerSession, $checkoutSession, $checkoutHelper);
    }
    
    public function getTokenRecieptUrl()
    {
        return $this->urlBuilder->getUrl('cybersource/manage/receipt');
    }
    
    public function getLocale()
    {
        return str_replace('_', '-', strtolower($this->locale));
    }

    /**
     * @param $cardAddress
     * @param bool $skipDecisionManager
     * @return string
     */
    public function buildTokenData($cardAddress, $skipDecisionManager = false)
    {
        $this->setTransactionType();

        $params = [
            'allow_payment_token_update' => "true",
            'access_key' => $this->gatewayConfig->getAccessKey(),
            'profile_id' => $this->gatewayConfig->getProfileId(),
            'ignore_avs' => $this->gatewayConfig->getIgnoreAvs(),
            'ignore_cvn' => $this->gatewayConfig->getIgnoreCvn(),
            'transaction_uuid' => uniqid(),
            'payment_method' => 'card',
            'signed_field_names' => $this->getSignedFields(true),
            'unsigned_field_names' => '',
            'signed_date_time' => gmdate("Y-m-d\\TH:i:s\\Z"),
            'locale' => $this->locale,
            'transaction_type' => $this->transactionType,
            'reference_number' => time(),
            'amount' => '0',
            'currency' => $this->getCurrentCurrencyCode(),
            'request_url' => $this->requestUrl,
            'override_custom_receipt_page' => $this->urlBuilder->getUrl('cybersource/manage/receipt'),
            'override_custom_cancel_page' => $this->urlBuilder->getUrl('cybersource/manage/cancel'),
            'partner_solution_id' => self::PARTNER_SOLUTION_ID,
        ];

        if ($this->gatewayConfig->getAuthIndicator() != AuthIndicator::UNDEFINED) {
            $params['auth_indicator'] = $this->gatewayConfig->getAuthIndicator();
        }
        
        $params = array_merge($params, $this->buildBillingAddress($cardAddress));

        $params[self::SAVE_BILLING_ADDRESS] = false;
        $params[self::USE_IFRAME] = false;
        $useIFrame = (bool) $this->gatewayConfig->getUseIframe();

        if (isset($cardAddress['save_billing'])) {
            $params[self::SAVE_BILLING_ADDRESS] = true;
        }

        if ($useIFrame) {
            $params[self::USE_IFRAME] =  true;
        }

        if ($this->_request->getParam('token')) {
            $params['payment_token'] = $this->_request->getParam('token');
            $params['signed_field_names'] = $params['signed_field_names'] . ',payment_token';
        }

        if ($skipDecisionManager) {
            $params['skip_decision_manager'] = 'true';
            $params['signed_field_names'] = $params['signed_field_names'] . ',skip_decision_manager';
        }
        
        if ($this->_request->getParam('reference_number')) {
            $params['reference_number'] = $this->_request->getParam('reference_number');
        }

        $params['unsigned_field_names'] = $this->getUnsignedFields($params);
        $params['signature'] = $this->sign($params, $this->gatewayConfig->getSecretKey());

        $this->_logger->info("token params = ".print_r($params, 1));
        
        return $this->renderHtml($params);
    }

    /**
     * @param array $cardAddress
     * @return array
     */
    private function buildBillingAddress($cardAddress)
    {
        $this->_logger->info(print_r($cardAddress, 1));
        return [
            'bill_to_forename' => $cardAddress['firstname'],
            'bill_to_surname' => $cardAddress['lastname'],
            'bill_to_email' => $this->customerSession->getCustomer()->getEmail(),
            'bill_to_company_name' => $cardAddress['company'],
            'bill_to_address_line1' => $cardAddress['street'][0],
            'bill_to_address_city' => $cardAddress['city'],
            'bill_to_address_postal_code' => $cardAddress['postcode'],
            'bill_to_address_state' => $this->region->load($cardAddress['region_id'])->getCode(),
            'bill_to_address_country' => $cardAddress['country_id'],
            'bill_to_phone' => $cardAddress['telephone'],
        ];
    }

    /**
     * @return void
     */
    public function setTransactionType()
    {
        $isTestMode = $this->gatewayConfig->isTestMode();

        if ($isTestMode) {
            $this->requestUrl = self::TEST_BASE_URL;
        }

        if ($this->_request->getParam('token')) {
            $this->transactionType = 'update_payment_token';
            $this->requestUrl .= self::TOKEN_UPDATE_URI;
        } else {
            $this->requestUrl .= self::TOKEN_CREATE_URI;
        }
    }

    private function renderHtml($params)
    {
        $html = '<html>
                    <head></head>
                    <body>
                        <form id="cybersource-create-token-form" action="'.$this->requestUrl.'" method="post">';
        foreach ($params as $name => $value) {
            $html .= '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
        }
        $html .= '</form><script type="text/javascript">document.getElementById("cybersource-create-token-form").submit()</script></body></html>';
        return $html;
    }
}
