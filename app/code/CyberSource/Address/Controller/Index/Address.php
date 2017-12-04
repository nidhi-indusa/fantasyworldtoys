<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Address\Controller\Index;

use CyberSource\Core\Service\CyberSourceSoapAPI;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\QuoteManagement;
use CyberSource\SecureAcceptance\Model\Token;
use Magento\Checkout\Model\Cart;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;

class Address extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CyberSourceSoapAPI
     */
    private $cyberSourceAPI;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * @var RequestDataBuilder
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Directory\Model\Region
     */
    private $regionModel;
    
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address
     */
    private $quoteAddress;
    

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        SessionManagerInterface $checkoutSession,
        QuoteManagement $quoteManagement,
        Token $token,
        Cart $cart,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        CyberSourceSoapAPI $cyberSourceAPI,
        \Magento\Directory\Model\Region $regionModel,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        RequestDataBuilder $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Model\Quote\Address $quoteAddress
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->token = $token;
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->cyberSourceAPI = $cyberSourceAPI;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->regionModel = $regionModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteAddress = $quoteAddress;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $region_id = $this->_request->getParam('region_id');
        if (!empty($region_id) && is_numeric($region_id)) {
             $region = $this->regionModel->load($region_id);
        }

        $shippingAddress = [
            'city' => $this->_request->getParam('city'),
            'country' => $this->_request->getParam('country'),
            'firstname' => $this->_request->getParam('firstname'),
            'lastname' => $this->_request->getParam('lastname'),
            'postcode' => $this->_request->getParam('postcode'),
            'region_code' => (!empty($region)) ? $region->getCode() : '',
            'street1' => $this->_request->getParam('street1'),
            'street2' => $this->_request->getParam('street2'),
            'telephone' => $this->_request->getParam('telephone')
        ];

        $this->quoteAddress->setData('city', $this->_request->getParam('city'));
        $this->quoteAddress->setData('country', $this->_request->getParam('country'));
        $this->quoteAddress->setData('firstname', $this->_request->getParam('firstname'));
        $this->quoteAddress->setData('lastname', $this->_request->getParam('lastname'));
        $this->quoteAddress->setData('postcode', $this->_request->getParam('postcode'));
        $this->quoteAddress->setData('region_code', (!empty($region)) ? $region->getCode() : '');
        $this->quoteAddress->setData('street1', $this->_request->getParam('street1'));
        $this->quoteAddress->setData('street2', $this->_request->getParam('street2'));
        $this->quoteAddress->setData('telephone', $this->_request->getParam('telephone'));
        
        $merchantId = $this->scopeConfig->getValue(
            "payment/chcybersource/merchant_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        $quote = $this->checkoutSession->getQuote();
        $quote->reserveOrderId();
        $this->checkoutSession->replaceQuote($quote);

        $data = [
            'needCheck' => (bool)$this->scopeConfig->getValue(
                "payment/chcybersource/address_check_enabled",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'needForce' => (bool)$this->scopeConfig->getValue(
                "payment/chcybersource/address_force_normal",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'updateFields' => [],
            'needUpdate' => false,
            'isValid' => false,
            'message' => '',
            'normalizationData' => []
        ];
        
        $needUpdate = false;
        $fieldsUpdate = [];
        $displayAddress = [];
                
        if ($this->scopeConfig->getValue(
            "payment/chcybersource/address_check_enabled",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            $result = $this->cyberSourceAPI->checkAddress(
                $merchantId,
                $quote->getReservedOrderId(),
                $shippingAddress,
                $this->quoteAddress
            );

            if (empty($result) || $result->reasonCode != 100) {
                if (!empty($result) && $result->reasonCode == 102 && !empty($result->invalidField)) {
                    $data['message'] = __(
                        'Validation address failed. Please check input address data. Field ' . $result->invalidField
                    );
                } else {
                    $data['message'] = __('Validation address failed. Please check input address data.');
                }
            } else {
                $data['isValid'] = true;
                $davReply = json_decode(json_encode($result->davReply), 1);
                $this->logger->info("davReply " . print_r($davReply, 1));
                $fieldsToCheck = [
                    'standardizedAddress1' => 'street1',
                    'standardizedAddress2' => 'street2',
                    'standardizedCity' => 'city',
                    'standardizedPostalCode' => 'postcode',
                ];

                foreach ($fieldsToCheck as $cybersourceKey => $magentoKey) {
                    if ((!empty($davReply[$cybersourceKey])) &&
                        $davReply[$cybersourceKey] != $shippingAddress[$magentoKey]
                    ) {
                        $needUpdate = true;
                        $fieldsUpdate[$magentoKey] = $davReply[$cybersourceKey];
                        if (preg_match('/^street(\d+)/', $magentoKey, $matches)) {
                            $data['normalizationData']['street[' . ($matches[1]-1).']'] = $davReply[$cybersourceKey];
                        } else {
                            $data['normalizationData'][$magentoKey] = $davReply[$cybersourceKey];
                        }
                    }
                }
                $this->logger->info("fields to update " . print_r($fieldsUpdate, 1));
                if ($needUpdate) {
                    foreach ($davReply as $key => $value) {
                        if (preg_match('/^standardized(.+)/', $key, $match) &&
                            !in_array($match[1], ['CSP', 'ISOCountry', 'AddressNoApt'])
                        ) {
                            $displayAddress[] = $match[1].': '.$value;
                        }
                    }
                    $data['updateFields'] = implode(',', $fieldsUpdate);
                    $data['message'] = __(
                        'Our address verification system has suggested your address should read as follows.' .
                        'Please review and confirm the suggested address is correct.'
                    ).'<br /><br />' . implode('<br />', $displayAddress);
                }
            }
        }
        $data['needUpdate'] = $needUpdate;
        $data['updateFields'] = $fieldsUpdate;
        $result = $this->resultJsonFactory->create();
        $result = $result->setData($data);
        return $result;
    }
}
