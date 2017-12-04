<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model;

use CyberSource\Core\Service\CyberSourceSoapAPI;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Paypal\Model\Express\Checkout as ExpressCheckout;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'chcybersource';
    const PAYMENT_METHOD_CARD = 'card';
    const PAYMENT_METHOD_PAY_PAL = 'paypal';

    protected $_code = 'chcybersource';
    protected $_canAuthorize = true;
    protected $_isOffline = false;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;

    /**
     * @var CyberSourceSoapAPI|null
     */
    private $gatewayAPI = null;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;
    
    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    private $quoteItemFactory;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Sales\Model\Service\OrderService
     */
    private $orderService;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $product;
    
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;
 
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $invoiceService;
 
    /**
     * @var \Magento\Framework\DB\Transaction
     */
    private $transaction;
    
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepositoryInterface;
    
    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    private $cartManagementInterface;

    /**
     * @var \CyberSource\Core\Model\ResourceModel\Token\Collection
     */
    private $tokenCollection;

    /**
     * Payment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param CyberSourceSoapAPI $cyberSourceAPI
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $product
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagementInterface
     * @param Session $checkoutSession
     * @param \CyberSource\Core\Model\ResourceModel\Token\Collection $collection
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        CyberSourceSoapAPI $cyberSourceAPI,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $product,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        Session $checkoutSession,
        \CyberSource\Core\Model\ResourceModel\Token\Collection $collection,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
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

        $this->messageManager = $messageManager;
        $this->quoteFactory = $quoteFactory;
        $this->gatewayAPI = $cyberSourceAPI;
        $this->checkoutSession = $checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->storeManager = $storeManager;
        $this->product = $product;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->productFactory = $productFactory;
        $this->tokenCollection = $collection;
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::authorize($payment, $amount);

        if ($this->checkoutSession->getIsRequestAuthorizeType()) {
            $payment->setIsTransactionClosed(false);
            $payment->setShouldCloseParentTransaction(false);
            $this->checkoutSession->setRequestTransactionType(null);
        }

        return $this;
    }

    /**
     * Capture payment abstract method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }

        if ($amount > $payment->getOrder()->getGrandTotal()) {
            $amount = $payment->getOrder()->getGrandTotal();
        }
        
        $payment->setTransactionId($payment->getAdditionalInformation('cc_trans_id'));

        if ($this->checkoutSession->getIsNotAdminCapture()) {
            $this->checkoutSession->setIsNotAdminCapture(null);
            return $this;
        }

        try {
            $this->gatewayAPI->setPayment($payment);
            $response = $this->gatewayAPI->captureOrder($amount);

            //try to use token on second failed invoice
            if (!$payment->getOrder()->getData('customer_is_guest') &&
                (empty($response) || $response->reasonCode != 100) &&
                $payment->getOrder()->getData('total_invoiced') > 0
            ) {
                $payment->setTransactionId($response->requestID);
                $this->tokenCollection->addFieldToFilter('order_id', $payment->getOrder()->getId());
                $this->tokenCollection->load();
                if ($this->tokenCollection->getSize() > 0) {
                    $quote = $this->quoteFactory->create()->load($payment->getOrder()->getQuoteId());
                    foreach ($this->tokenCollection as $token) {
                        $this->gatewayAPI->tokenPayment($token->getData(), false, true, $quote, $amount);
                        break;
                    }
                }
            } elseif ($payment->getOrder()->getData('total_invoiced') > 0) {
                $payment->setTransactionId(null);
                if ($payment->getOrder()->getData('customer_is_guest')) {
                    $this->messageManager->addError(
                        __("Your payment processor does not allow multiple captures per authorization 
                            and this order was created by guest so there is no option to create a new order")
                    );
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Void Captured Payment. Try to perform cancel if void fail
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $this->gatewayAPI->setPayment($payment);
            $this->gatewayAPI->voidOrderPayment($payment->getOrder()->getStoreId());

            if ($this->gatewayAPI->isSuccessfullyVoided()) {
                $this->cancel($payment);
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Cancel a payment and reverse authorization at CyberSource
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $this->gatewayAPI->setPayment($payment);
            $this->gatewayAPI->reverseOrderPayment($payment->getOrder()->getStoreId());
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Perform a refund if Void and Reversal was successfully
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->gatewayAPI->setPayment($payment);
        if (!$this->gatewayAPI->refundOrderPayment($amount)) {
            throw new LocalizedException(__('Payment gateway refunding error.'));
        }
        return $this;
    }
}
