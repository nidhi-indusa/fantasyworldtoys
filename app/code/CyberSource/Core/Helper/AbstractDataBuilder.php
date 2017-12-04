<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Helper;

use Magento\Customer\Model\Session;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Group;

abstract class AbstractDataBuilder extends AbstractHelper
{
    const HMAC_SHA256 = 'sha256';
    
    /**
     * @var string
     */
    public $merchantId;

    /**
     * @var string
     */
    public $transactionKey;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    public $checkoutHelper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var array
     */
    public $excludedPayerAuthenticationKeys = [
        'payer_authentication_proof_xml',
        'payer_authentication_validate_result',
        'request_token'
    ];
    
    /**
     * AbstractDataBuilder constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param SessionManagerInterface $customerSession
     * @param SessionManagerInterface $checkoutSession
     * @param \Magento\Checkout\Helper\Data $data
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        SessionManagerInterface $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $data
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->checkoutHelper  = $data;
        $this->urlBuilder = $context->getUrlBuilder();
    }

    /**
     * @param $params
     * @param $secretKey
     * @return string
     */
    public function sign($params, $secretKey)
    {
        return base64_encode(
            hash_hmac(
                self::HMAC_SHA256,
                $this->buildDataToSign($params),
                $secretKey,
                true
            )
        );
    }

    /**
     * @param $params
     * @return string
     */
    public function buildDataToSign($params)
    {
        $signedFieldNames = explode(",", $params["signed_field_names"]);
        $dataToSign = [];
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . "=" . $params[$field];
        }
        return implode(",", $dataToSign);
    }

    /**
     * Get checkout method
     *
     * @param Quote $quote
     * @return string
     */
    public function getCheckoutMethod(Quote $quote)
    {
        if ($this->customerSession->isLoggedIn()) {
            return Onepage::METHOD_CUSTOMER;
        }
        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(Onepage::METHOD_REGISTER);
            }
        }

        return $quote->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @param Quote $quote
     * @return void
     */
    public function prepareGuestQuote(Quote $quote)
    {
        $quote->setCustomerId(null);
        $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
    }


    /**
     * @param bool $isToken
     * @param bool $isSilent
     * @param bool $manageToken
     * @return string
     */
    public function getSignedFields($isToken = false, $isSilent = false, $manageToken = false)
    {
        if ($manageToken) {
            $signedFields = [
                'access_key',
                'profile_id',
                'transaction_uuid',
                'signed_field_names',
                'unsigned_field_names',
                'signed_date_time',
                'locale',
                'transaction_type',
                'reference_number',
                'amount',
                'currency',
                'payment_method',
                'bill_to_forename',
                'bill_to_surname',
                'bill_to_email',
                'bill_to_phone',
                'bill_to_address_line1',
                'bill_to_address_city',
                'bill_to_address_state',
                'bill_to_address_country',
                'bill_to_address_postal_code'
            ];
            
            if ($isSilent) {
                $signedFields[] = 'override_custom_receipt_page';
            }
        } elseif ($isSilent) {
            $signedFields = [
                'access_key',
                'profile_id',
                'transaction_uuid',
                'signed_field_names',
                'unsigned_field_names',
                'signed_date_time',
                'locale',
                'transaction_type',
                'reference_number',
                'amount',
                'currency',
                'payment_method',
                'bill_to_forename',
                'bill_to_surname',
                'bill_to_email',
                'bill_to_phone',
                'bill_to_address_line1',
                'bill_to_address_city',
                'bill_to_address_state',
                'bill_to_address_country',
                'bill_to_address_postal_code',
                //payer auth fields
                'payer_auth_enroll_service_run',
                'partner_solution_id'
            ];
        
            if ($isToken) {
                $signedFields[] = 'override_custom_receipt_page';
            }
        } else {
            $signedFields = [
                'access_key',
                'profile_id',
                'ignore_avs',
                'ignore_cvn',
                'transaction_uuid',
                'signed_field_names',
                'unsigned_field_names',
                'signed_date_time',
                'locale',
                'transaction_type',
                'reference_number',
                'amount',
                'currency',
                'override_custom_receipt_page',
                'override_custom_cancel_page',
                'partner_solution_id',
            ];

            if (!$isToken) {
                $signedFields[] = 'tax_amount';
                $signedFields[] = 'card_number';
            }
        }

        return implode(",", $signedFields);
    }

    /**
     * @param $params
     * @return string
     */
    public function getUnsignedFields($params)
    {
        $signedFieldNames = explode(",", $params["signed_field_names"]);
        $unsignedFieldNames = [];
        foreach ($params as $key => $field) {
            if (in_array($key, $signedFieldNames) === false) {
                $unsignedFieldNames[] = $key;
            }
        }
        return implode(",", $unsignedFieldNames);
    }

    /**
     * Setup Credentials for webservice
     * @param $merchantId
     * @param $transactionKey
     */
    public function setUpCredentials($merchantId, $transactionKey)
    {
        $this->merchantId = $merchantId;
        $this->transactionKey = $transactionKey;
    }

    /**
     * @param float $amount
     * @return string
     */
    public function formatAmount($amount)
    {
        if (!is_float($amount)) {
            $amount = (float) $amount;
        }
        
        return number_format($amount, 2, '.', '');
    }

    public function getCcTypes()
    {
        return [
            'VI' => ['code' => '001', 'name' => 'Visa'],
            'MC' => ['code' => '002', 'name' => 'MasterCard'],
            'AE' => ['code' => '003', 'name' => 'American Express'],
            'DI' => ['code' => '004', 'name' => 'Discover'],
        ];
    }
    
    public function getCardName($cardCode)
    {
        $cardNames = [
            "001" => "Visa",
            "002" => "MasterCard",
            "003" => "American Express",
            "004" => "Discover",
            "005" => "Diners Club",
            "006" => "Carte Blanche",
            "007" => "JCB",
            "014" => "EnRoute",
            "021" => "JAL",
            "024" => "Maestro UK Domestic",
            "031" => "Delta",
            "033" => "Visa Electron",
            "034" => "Dankort",
            "036" => "Carte Bleue",
            "037" => "Carta Si",
            "042" => "Maestro International",
            "043" => "GE Money UK card",
            "050" => "Hipercard (sale only)",
            "054" => "Elo"
        ];

        if (empty($cardCode) || array_key_exists($cardCode, $cardNames) === false) {
            return "";
        }

        return $cardNames[$cardCode];
    }

    /**
     * Gateway error response wrapper
     *
     * @param string $text
     * @return \Magento\Framework\Phrase
     */
    public function wrapGatewayError($text)
    {
        return __('Gateway error: %1', $text);
    }

    /**
     * Return only payer_authentication info from Cybersource Response
     *
     * @param $request
     * @return array
     */
    public function getPayerAuthenticationData($request)
    {
        $keys = preg_grep("/^(payer_authentication_)/", array_keys($request), 0);

        if (empty($keys)) {
            return [];
        }

        $payerAuthenticationData = [];
        foreach ($keys as $key) {
            if (!in_array($key, $this->excludedPayerAuthenticationKeys)) {
                $payerAuthenticationData[$key] = $request[$key];
            }
        }

        return $payerAuthenticationData;
    }

    public function getCurrentCurrencyCode()
    {
        return $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
    }
}
