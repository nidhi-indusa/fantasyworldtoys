<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Manage;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\QuoteManagement;
use CyberSource\SecureAcceptance\Model\Token;
use Magento\Checkout\Model\Cart;
use CyberSource\SecureAcceptance\Helper\TokenRequestDataBuilder;

class GetSignedFields extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var Cart
     */
    private $cart;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    
    /**
     * @var TokenRequestDataBuilder
     */
    private $helper;
    
    /**
     * @var
     */
    private $region;
    
    /**
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * GetSignedFields constructor.
     * @param Context $context
     * @param SessionManagerInterface $checkoutSession
     * @param SessionManagerInterface $customerSession
     * @param QuoteManagement $quoteManagement
     * @param Token $token
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param TokenRequestDataBuilder $helper
     * @param \Magento\Directory\Model\Region $region
     * @param Cart $cart
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        SessionManagerInterface $checkoutSession,
        SessionManagerInterface $customerSession,
        QuoteManagement $quoteManagement,
        Token $token,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        TokenRequestDataBuilder $helper,
        \Magento\Directory\Model\Region $region,
        Cart $cart,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteManagement = $quoteManagement;
        $this->token = $token;
        $this->cart = $cart;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->region = $region;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->scopeConfig->getValue("payment/chcybersource/test_mode", \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $config_service_url = 'sop_service_url_test';
        } else {
            $config_service_url = 'sop_service_url';
        }
        $token = $this->_request->getParam('token', '');
        $isMultiShipping = $this->_request->getParam('multishipping', false);
        $this->checkoutSession->setIsMultiShipping($isMultiShipping);
        $reference_number = $this->_request->getParam('reference_number', '');
        $data = [];
        $data['access_key'] = $this->scopeConfig->getValue('payment/chcybersource/sop_access_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $data['profile_id'] = $this->scopeConfig->getValue('payment/chcybersource/sop_profile_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $data['transaction_uuid'] = uniqid();
        $data['signed_field_names'] = $this->helper->getSignedFields(false, true, true);
        $data['unsigned_field_names'] = (!empty($token)) ? '' : 'card_type,card_number,card_expiry_date,card_cvn';
        $data['signed_date_time'] = gmdate("Y-m-d\\TH:i:s\\Z");
        $data['locale'] = $this->helper->getLocale();
        $data['transaction_type'] = (!empty($token)) ? 'update_payment_token' : 'create_payment_token';
        $data['reference_number'] = (!empty($reference_number)) ? $reference_number : time();
        $data['amount'] = 0;
        $data['currency'] = 'USD';
        $data['payment_method'] = 'card';
        $data['override_custom_receipt_page'] = $this->helper->getTokenRecieptUrl();
        $data['bill_to_forename'] = $this->_request->getParam('firstname', '');
        $data['bill_to_surname'] = $this->_request->getParam('lastname', '');
        $data['bill_to_email'] = $this->customerSession->getCustomer()->getEmail();
        $data['bill_to_phone'] = $this->_request->getParam('telephone', '');
        $data['bill_to_address_line1'] = $this->_request->getParam('street_1', '');
        $data['bill_to_address_city'] = $this->_request->getParam('city', '');
        $data['bill_to_address_state'] = $this->_request->getParam('region_id', '');
        $data['bill_to_address_country'] = $this->_request->getParam('country', '');
        $data['bill_to_address_postal_code'] = $this->_request->getParam('zip', '');
        
        if (!empty($token)) {
            $data['payment_token'] = $token;
            $data['signed_field_names'] .= ',payment_token';
        }
        
        if (is_numeric($data['bill_to_address_state'])) {
            $region = $this->region->load($data['bill_to_address_state']);
            $data['bill_to_address_state'] = $region->getCode();
        }
        
        $data['skip_decision_manager'] = 'true';
        $data['signed_field_names'] .= ',skip_decision_manager';
        
        $data['signature'] = $this->helper->sign($data, $this->scopeConfig->getValue('payment/chcybersource/sop_secret_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        $result = $this->resultJsonFactory->create();
        $result = $result->setData([
            'action_url' => (!empty($token))
                ? $this->scopeConfig->getValue("payment/chcybersource/".$config_service_url, \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'/silent/token/update'
                : $this->scopeConfig->getValue("payment/chcybersource/".$config_service_url, \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'/silent/token/create',
            'form_data' => $data
        ]);
        return $result;
    }
}
