<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Controller\Index;

use CyberSource\SecureAcceptance\Model\Payment;
use CyberSource\Core\Service\CyberSourceSoapAPI;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use CyberSource\SecureAcceptance\Model\Token;
use Magento\Checkout\Model\Cart;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;

class Failure extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    protected $_session;

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
    protected $_customerSession;

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
     * Receipt constructor.
     * @param Context $context
     * @param Session $session
     * @param QuoteManagement $quoteManagement
     * @param Token $token
     * @param Cart $cart
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param LoggerInterface $logger
     * @param CyberSourceSoapAPI $cyberSourceAPI
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param RequestDataBuilder $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Session $session,
        QuoteManagement $quoteManagement,
        Token $token,
        Cart $cart,
        StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        LoggerInterface $logger,
        CyberSourceSoapAPI $cyberSourceAPI,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        RequestDataBuilder $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_session = $session;
        $this->_quoteManagement = $quoteManagement;
        $this->_token = $token;
        $this->_cart = $cart;
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->_logger = $logger;
        $this->_cyberSourceAPI = $cyberSourceAPI;
        $this->_orderPaymentRepository = $orderPaymentRepository;
        $this->_helper = $helper;
        $this->scopeConfig = $scopeConfig;
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
        $quote = $this->_session->getQuote();
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_storeManager->getStore()->getBaseUrl() .'checkout/cart');
        $this->messageManager->addErrorMessage(__("Something went wrong. Please try later"));
        return $resultRedirect;
    }
}
