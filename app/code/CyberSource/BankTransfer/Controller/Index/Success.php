<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Controller\Index;

use CyberSource\Core\Service\CyberSourceSoapAPI;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\QuoteManagement;
use CyberSource\SecureAcceptance\Model\Token;
use Magento\Checkout\Model\Cart;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;

class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * @var Token
     */
    protected $_token;

    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var CyberSourceSoapAPI
     */
    protected $_cyberSourceAPI;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $_orderPaymentRepository;

    /**
     * @var RequestDataBuilder
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * Success constructor.
     * @param Context $context
     * @param QuoteManagement $quoteManagement
     * @param Token $token
     * @param Cart $cart
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param CyberSourceSoapAPI $cyberSourceAPI
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param RequestDataBuilder $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param SessionManagerInterface $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        QuoteManagement $quoteManagement,
        Token $token,
        Cart $cart,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        CyberSourceSoapAPI $cyberSourceAPI,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        RequestDataBuilder $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        SessionManagerInterface $checkoutSession
    ) {
        $this->_quoteManagement = $quoteManagement;
        $this->_token = $token;
        $this->_cart = $cart;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
        $this->_cyberSourceAPI = $cyberSourceAPI;
        $this->_orderPaymentRepository = $orderPaymentRepository;
        $this->_helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $responses = $this->checkoutSession->getData('response');
        $paymentMethod = $this->checkoutSession->getData('bank_payment_method');
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        try {
            // Set CustomerData to quote
            if (!$this->customerSession->isLoggedIn()) {
                $quote->setCustomerIsGuest(1);
                $quote->setCheckoutMethod('guest');
                $quote->setCustomerId(null);
                $quote->setCustomerEmail($this->checkoutSession->getData('guestEmail'));
                $quote->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                $this->checkoutSession->setData('guestEmail', null);
            }
            $quote->setPaymentMethod('cybersource_bank_transfer');
            $quote->setInventoryProcessed(false);
            $quote->save();
            $quote->getPayment()->setAdditionalInformation($responses);
            $quote->getPayment()->setTransactionId($responses->apSaleReply->processorTransactionID);
            
            $quote->getPayment()->setMethod('cybersource_bank_transfer');

            // Collect Totals & Save Quote
            $quote->collectTotals()->save();

            $this->checkoutSession->setIsNotAdminCapture(true);
            // Create Order From Quote
            $quote->setReservedOrderId($responses->merchantReferenceCode);
            $order = $this->_quoteManagement->submit($quote);
            $payment = $order->getPayment();
            $payment->setLastTransId($responses->requestID);
            $payment->setAdditionalInformation(['request_id' => $responses->requestID]);
            $payment->setAdditionalData($responses->requestToken);
            $this->_orderPaymentRepository->save($payment);
            $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->checkoutSession->setLastQuoteId($quote->getId());
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());
            $this->checkoutSession->setLastRealOrderId($order->getRealOrderId());
            $this->_cart->truncate()->save();
            $resultRedirect->setUrl($this->_url->getUrl('checkout/onepage/success'));
            $this->messageManager->addSuccessMessage(__('Your order has been successfully created!'));
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
       
        return $resultRedirect;
    }
}
