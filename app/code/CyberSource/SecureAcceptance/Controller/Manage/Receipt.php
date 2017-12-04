<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Manage;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\QuoteManagement;
use CyberSource\SecureAcceptance\Model\Token;
use Magento\Checkout\Model\Cart;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\Country;
use Psr\Log\LoggerInterface;
use CyberSource\Core\Service\CyberSourceSoapAPI;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;

class Receipt extends \Magento\Framework\App\Action\Action
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Region
     */
    private $region;

    /**
     * @var Country
     */
    private $country;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CyberSourceSoapAPI
     */
    private $cyberSourceAPI;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        SessionManagerInterface $checkoutSession,
        SessionManagerInterface $customerSession,
        QuoteManagement $quoteManagement,
        Token $token,
        Cart $cart,
        StoreManagerInterface $storeManager,
        Region $region,
        Country $country,
        LoggerInterface $logger,
        CyberSourceSoapAPI $cyberSourceAPI
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteManagement = $quoteManagement;
        $this->token = $token;
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->region = $region;
        $this->country = $country;
        $this->logger = $logger;
        $this->cyberSourceAPI = $cyberSourceAPI;
        parent::__construct($context);
    }
 
    public function execute()
    {
        $responses = $this->getRequest()->getParams();
        $this->logger->info(print_r($responses, 1));
        $storeId = $this->storeManager->getStore()->getId();
        $url = $this->_url->getUrl('cybersource/manage/card');
        $isMultiShipping = $this->checkoutSession->getIsMultiShipping();
        
        if (isset($responses['decision']) && $responses['decision'] == 'DECLINE') {
            $this->messageManager->addErrorMessage(__('Sorry but your transaction was unsuccessful.'));
            $url = $this->_url->getUrl('cybersource/manage/card');
        }

        if ($isMultiShipping) {
            $url = $this->_url->getUrl('multishipping/checkout/billing');
        }
        
        //100 - success, 480 review
        if (isset($responses['reason_code']) && $responses['reason_code'] == 100) {
            if (isset($responses['req_reference_number'])) {
                $this->token->load($responses['req_reference_number'], 'reference_number');
                if ($this->token->getId()) {
                    $updateMessage = __('You have updated the token successfully.');
                } else {
                    $updateMessage = __('You have created a token successfully.');
                }
            }

            /**
             * when creating a token,
             * the token string is in 'payment_token'
             * when updating a token it's in 'req_payment_token'
             */
            $_tokenString = isset($responses['req_payment_token']) ?
                $responses['req_payment_token'] :
                $responses['payment_token'];

            $profile = null;
            if (array_key_exists('req_reference_number', $responses) && $responses['req_reference_number'] !== null) {
                $profile = $this->cyberSourceAPI->retrieveProfile(
                    $_tokenString,
                    $responses['req_reference_number'],
                    $storeId
                );
            }

            $ccLast4 = '';
            if ($profile !== null && $profile->reasonCode === 100) {
                $ccLast4 = "****-****-****-" . substr($profile->paySubscriptionRetrieveReply->cardAccountNumber, -4);
            }

            $tokenInfo = $this->buildTokenInfo($_tokenString, $responses, $storeId, $ccLast4);

            if (isset($responses['req_transaction_type']) &&
                $responses['req_transaction_type'] == 'update_payment_token'
            ) {
                $responses['payment_token'] = $responses['req_payment_token'];
            }

            if (isset($responses['payment_token'])) {
                $profile = $this->cyberSourceAPI->retrieveProfile(
                    $responses['payment_token'],
                    $responses['req_reference_number'],
                    $storeId
                );
                $this->logger->info("profile: ".print_r($profile, 1));
                if ($profile !== null && $profile->reasonCode === 100) {
                    $tokenInfo['cc_last4'] = "****-****-****-" . substr(
                        $profile->paySubscriptionRetrieveReply->cardAccountNumber,
                        -4
                    );
                } elseif (!empty($responses['req_card_number'])) {
                    $tokenInfo['cc_last4'] = "****-****-****-" . substr($responses['req_card_number'], -4);
                }
                $tokenInfo['payment_token'] = $responses['payment_token'];
            }

            $this->token->addData($tokenInfo);
            try {
                $address = $this->_prepareAddresses($responses);
                if (isset($responses['req_merchant_defined_data10']) &&
                    (int) $responses['req_merchant_defined_data10']
                ) {
                    //CASE CREATE NEW ADDRESS
                    if ($this->token->getData('address_id')) {
                        //CASE EDIT ADDRESS
                        $address['id'] = $this->token->getData('address_id');
                    }
                    $this->_eventManager->dispatch('create_token_after', ['addresses' => $address]);
                }
                if ($addressId = $this->customerSession->getData('address_id')) {
                    $this->customerSession->setData('address_id', '');
                    $this->token->setData('address_id', $addressId);
                }
                $this->token->save();
                
                if (isset($updateMessage)) {
                    $this->messageManager->addSuccessMessage($updateMessage);
                } else {
                    $this->messageManager->addSuccessMessage(__('You have created token successful.'));
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        } elseif ($responses['reason_code'] == 480 || $responses['reason_code'] == 102) { //review or error
            $this->messageManager->addErrorMessage(
                __('Sorry but we are unable to create or update your token at this time. ' . $responses['reason_code'])
            );
        }
        if (!isset($responses[RequestDataBuilder::REQ_USE_IFRAME])) {
            return $this->getResponse()->setRedirect($url);
        }

        $html = '<html>
                    <body>
                        <script type="text/javascript">
                            window.onload = function() {
                                window.top.location.href = "'.$url.'";
                            };
                        </script>
                    </body>
                </html>';

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $resultRedirect->setContents($html);
        return $resultRedirect;
    }

    private function buildTokenInfo(
        $tokenString,
        $responses,
        $storeId,
        $ccLast4
    ) {
        return [
            'created_date' => gmdate("Y-m-d\\TH:i:s\\Z"),
            'customer_id' => $this->customerSession->getId(),
            'payment_token' => $tokenString,
            'transaction_id' => isset($responses['transaction_id']) ? $responses['transaction_id'] : '',
            'store_id' => $storeId,
            'card_expire' => isset($responses['req_card_expiry_date']) ? $responses['req_card_expiry_date'] : '',
            'card_type' => isset($responses['req_card_type']) ? $responses['req_card_type'] : '',
            'updated_date' => gmdate("Y-m-d\\TH:i:s\\Z"),
            'cc_number' => isset($responses['req_card_number']) ? $responses['req_card_number'] : '',
            'cc_last4' => $ccLast4,
            'card_expiry_date' => isset($responses['req_card_expiry_date']) ? $responses['req_card_expiry_date'] : '',
            'reference_number' => isset($responses['req_reference_number']) ? $responses['req_reference_number'] : '',
            'authorize_only' => 1,
            'customer_email' => isset($responses['req_bill_to_email']) ? $responses['req_bill_to_email'] : '',
            'payment_type' => isset($responses['req_transaction_type']) ? $responses['req_transaction_type'] : '',
        ];
    }
    
    private function _prepareAddresses($responses)
    {
        $addresses = [];
        if (isset($responses['req_bill_to_forename'])) {
            $addresses['firstname'] = $responses['req_bill_to_forename'];
        }
        if (isset($responses['req_bill_to_company_name'])) {
            $addresses['company'] = $responses['req_bill_to_company_name'];
        }
        if (isset($responses['req_bill_to_surname'])) {
            $addresses['lastname'] = $responses['req_bill_to_surname'];
        }
        $addresses['company'] = '';
        if (isset($responses['req_bill_to_company_name'])) {
            $addresses['company'] = $responses['req_bill_to_company_name'];
        }
        if (isset($responses['req_bill_to_address_line1'])) {
            $addresses['street'][] = $responses['req_bill_to_address_line1'];
        }
        if (isset($responses['req_bill_to_address_city'])) {
            $addresses['city'] = $responses['req_bill_to_address_city'];
        }
        if (isset($responses['req_bill_to_address_postal_code'])) {
            $addresses['postcode'] = $responses['req_bill_to_address_postal_code'];
        }
        if (isset($responses['req_bill_to_address_country'])) {
            $addresses['country_id'] = $responses['req_bill_to_address_country'];
        }
        
        if (isset($responses['req_bill_to_address_country']) && isset($responses['req_bill_to_address_state'])) {
            $addresses['region_id'] = $this->region->loadByCode(
                $responses['req_bill_to_address_state'],
                $responses['req_bill_to_address_country']
            )->getId();
        }
        if (isset($responses['req_bill_to_phone'])) {
            $addresses['telephone'] = $responses['req_bill_to_phone'];
        }
        $addresses['vat_id'] = false;
        
        return $addresses;
    }
}
