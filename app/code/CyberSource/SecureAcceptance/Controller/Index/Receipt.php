<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

use CyberSource\SecureAcceptance\Model\Payment;
use CyberSource\Core\Service\CyberSourceSoapAPI;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use CyberSource\SecureAcceptance\Model\Token;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;

class Receipt extends \Magento\Framework\App\Action\Action
{
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
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CyberSourceSoapAPI
     */
    private $cyberSourceAPI;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var RequestDataBuilder
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Sales\Model\Order\Status $status
     */
    private $status;
    
    /**
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory
     */
    private $historyCollectionFactory;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    
    /**
     * Receipt constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param QuoteManagement $quoteManagement
     * @param Token $token
     * @param Cart $cart
     * @param StoreManagerInterface $storeManager
     * @param SessionManagerInterface $checkoutSession
     * @param SessionManagerInterface $customerSession
     * @param LoggerInterface $logger
     * @param CyberSourceSoapAPI $cyberSourceAPI
     * @param OrderRepository $orderRepository
     * @param RequestDataBuilder $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param InvoiceRepository $invoiceRepository
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        QuoteManagement $quoteManagement,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        Token $token,
        Cart $cart,
        StoreManagerInterface $storeManager,
        SessionManagerInterface $checkoutSession,
        SessionManagerInterface $customerSession,
        LoggerInterface $logger,
        CyberSourceSoapAPI $cyberSourceAPI,
        OrderRepository $orderRepository,
        RequestDataBuilder $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\Order\Status $status,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollectionFactory,
        InvoiceRepository $invoiceRepository,
        \Magento\Framework\Registry $registry,
        QuoteRepository $quoteRepository
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->quoteFactory = $quoteFactory;
        $this->token = $token;
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->cyberSourceAPI = $cyberSourceAPI;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->status = $status;
        $this->historyCollectionFactory = $historyCollectionFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->registry = $registry;
        $this->quoteRepository = $quoteRepository;
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
        $responses = $this->getRequest()->getParams();
        $url = $this->_url->getUrl('checkout/cart');

        if(!$this->validateSignature($responses)) {
            $this->messageManager->addErrorMessage(__('Payment could not be processed.'));
        } else {
            $this->logger->info("receipt response: ".print_r($responses, 1));
            $decision = (!empty($responses['decision'])) ? $responses['decision'] : null;
            switch ($decision) {
                case 'DECLINE':
                case 'REJECT':
                case 'ERROR':
                    $decision = 'REJECT';
                    $url = $this->processSuccess($responses, $url, $decision);
                    break;

                case 'REVIEW':
                    $url = $this->processSuccess($responses, $url, $decision);
                    break;

                default:
                    if (isset($responses['reason_code']) && 100 == $responses['reason_code']) {
                        $url = $this->processSuccess($responses, $url, $decision);
                    }
            }
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

    /**
     * @param $responses
     * @param $url
     * @param $decision
     * @return string
     * @throws LocalizedException
     */
    private function processSuccess($responses, $url, $decision)
    {
        if (isset($responses['req_reference_number'])) {

            try {
                $collection = $this->quoteFactory->create()->getCollection();
                $collection->addFilter('reserved_order_id', $responses['req_reference_number']);
                /** @var $quote Quote */
                $quote = $collection->getFirstItem();

                /**
                 * Create a new quote to be able to keep it when redirect back to cart
                 */
                if ($decision == 'REJECT') {
                    $oldQuote = $this->quoteFactory->create();
                    $quote->setReservedOrderId(null);
                    $quoteData = $quote->getData();
                    $oldQuote->setData($quoteData);
                }

                if (!$quote->getId() || !$quote->getIsActive()) {
                    throw new \Exception(sprintf(__('Could not find shopping cart: %s'), $responses['req_reference_number']));
                }

                $this->logger->info("ref number = ".$responses['req_reference_number']);
                $this->logger->info("count visible quote items = ".count($quote->getAllVisibleItems()));
                $customerId = (int)$quote->getCustomerId();

                if (isset($responses['req_bill_to_email'])) {
                    $quote->setCustomerEmail($responses['req_bill_to_email']);
                }
                // Set CustomerData to quote
                if (!$customerId) {
                    $quote->setCustomerIsGuest(1);
                    $quote->setCheckoutMethod('guest');
                    $quote->setCustomerId(null);
                    $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
                    $quote->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                    $customerId = 0;
                }
                $quote->setPaymentMethod('cybersource');
                $quote->setInventoryProcessed(false);
                $quote->getPayment()->setAdditionalInformation([
                        'method' => Payment::CODE,
                        'last_trans_id' => $responses['transaction_id'],
                        'cc_trans_id' => $responses['transaction_id'],
                        'cardType' => $responses['req_card_type'],
                        'last4' => substr($responses['req_card_number'], -4),
                        'sa_type' => $this->scopeConfig->getValue(
                            "payment/chcybersource/secureacceptance_type",
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        ),
                ]);
                
                $quote->getPayment()->setCcType($responses['req_card_type']);
                $quote->getPayment()->setCcExpMonth(substr($responses['req_card_expiry_date'], 0, 2));
                $quote->getPayment()->setCcExpYear(substr($responses['req_card_expiry_date'], 3));
                $quote->getPayment()->setTransactionId($responses['transaction_id']);
                $quote->getPayment()->setMethod(Payment::CODE);

                // Collect Totals & Save Quote
                $quote->collectTotals();
                $quoteId = $quote->getId();
                if ($decision == 'REVIEW' || preg_match('/authorization/', $responses['req_transaction_type'])) {
                    $this->checkoutSession->setIsRequestAuthorizeType(true);
                }
                $this->checkoutSession->setIsNotAdminCapture(true);
                // Create Order From Quote
                $this->logger->info("quote id = ". $quoteId);
                $this->logger->info("RESERVED_ORDER_ID BEFORE SAVE ORDER= " . $quote->getReservedOrderId());
                $order = $this->quoteManagement->submit($quote);
                $this->logger->info("RESERVED_ORDER_ID AFTER SAVE ORDER= " . $quote->getReservedOrderId());

                if (is_null($order) || !$order->getId()) {
                    throw new \Exception(__('Order could not be created'));
                }

                $this->logger->info("order id = ". $order->getId());
                /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $payment */
                $payment = $order->getPayment();
                $payment->setCcTransId($responses['transaction_id']);
                $payment->setLastTransId($responses['transaction_id']);
                $payment->setAdditionalData($responses['request_token']);

                if ($decision == 'REVIEW') {
                    $order->setState('payment_review');
                    $order->setStatus($this->getStatusByState('payment_review'));
                    $this->updateInvoiceState($order, $decision);
                }

                if ($decision == 'REJECT') {
                    $order->setState(Order::STATE_CANCELED);
                    $order->setStatus($this->getStatusByState(Order::STATE_CANCELED));
                    $this->updateInvoiceState($order, $decision);
                    $this->orderRepository->save($order);
                    $oldQuote->setReservedOrderId(null);
                    $this->quoteRepository->save($oldQuote);
                    $this->checkoutSession->replaceQuote($oldQuote);
                    $this->checkoutSession->resetCheckout();
                    $quote = $oldQuote;
                    $this->updateTransaction($responses['transaction_id'], $order, 'Cancelled');
                    throw new LocalizedException(__('Sorry but your transaction was unsuccessful.'));
                }

                $payerAuthenticationData = $this->helper->getPayerAuthenticationData($responses);
                if (!empty($payerAuthenticationData)) {
                    $payerAuthenticationData['request_token'] = $responses['request_token'];
                    $payerAuthenticationData['payment_token'] = (!empty($responses['req_payment_token'])) ?
                        $responses['req_payment_token'] :
                        null;
                    $additionalData = array_merge($payment->getAdditionalInformation(), $payerAuthenticationData);
                    $additionalData = array_merge($additionalData, ['request_id' => $responses['transaction_id']]);
                } else {
                    $additionalData = array_merge($payment->getAdditionalInformation(), ['request_id' => $responses['transaction_id']]);
                }
                $payment->setAdditionalInformation($additionalData);
                
                if ((int)$this->checkoutSession->getData('isRequestAuthorizeType')
                    || (!empty($responses['req_transaction_type']) && preg_match('/authorization/', $responses['req_transaction_type']))) {
                    $this->logger->info("auth receipt");
                    if ($decision != 'REVIEW') {
                        $order->setState('pending_payment');
                        $order->setStatus($this->getStatusByState('pending_payment'));
                    }
                    $this->checkoutSession->setIsRequestAuthorizeType(null);
                } elseif ($decision == 'REVIEW') {
                    $this->logger->info("capture review receipt");
                } else { //regular capture
                    $this->logger->info("capture regular receipt");
                    $order->setState('processing');
                    $order->setStatus($this->getStatusByState('processing'));
                }

                $this->orderRepository->save($order);
                $this->quoteRepository->save($quote);

                $url = $this->_url->getUrl('cybersource/index/beforesuccess', [
                    'order_id' => $order->getId(),
                    'quote_id' => $quote->getId(),
                    'order_status' => $order->getStatus(),
                    'real_order_id' => $order->getRealOrderId()
                ]);
                $this->_saveToken($responses, $customerId, $order->getId(), $quoteId);
                if ($decision == 'REVIEW') {
                    $this->updateTransaction($responses['transaction_id'], $order, 'Review');
                }
            } catch (\Exception $e) {
                $this->logger->error("some error: ".$e->getMessage());
                $this->messageManager->addErrorMessage(__($e->getMessage()));
            }
        }

        return $url;
    }

    private function validateSignature($responses)
    {
        $saType = $this->scopeConfig->getValue(
            "payment/chcybersource/secureacceptance_type",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($saType == \CyberSource\Core\Model\Source\SecureAceptance\Type::SA_WEB) {
            $path = "payment/chcybersource/secret_key";
        } else {
            $path = "payment/chcybersource/sop_secret_key";
        }

        $transactionKey = $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!array_key_exists("signed_field_names", $responses)) {
            return false;
        }

        $signedKey = $this->helper->sign($responses, $transactionKey);

        return $signedKey === $responses['signature'];
    }
    
    private function updateInvoiceState($order, $decision)
    {
        $this->registry->register('isSecureArea', true);

        /** @var \Magento\Sales\Model\Order $order */
        $invoice = $order->getInvoiceCollection()->getFirstItem();

        /**
         * When module is configured to auth only and payment is caught by DM
         * there is no invoice to be updated, so we just return
         */
        if (!$invoice->hasData()) {
            return null;
        }

        $invoiceState = Invoice::STATE_OPEN;

        if ($decision == "REJECT") {
            $invoiceState = Invoice::STATE_CANCELED;
        }

        /** @var \Magento\Sales\Api\Data\InvoiceInterface $invoice */
        $invoice->setState($invoiceState);

        $this->invoiceRepository->save($invoice);

        $order->setData('base_total_paid', 0);
        $order->setData('base_shipping_invoiced', 0);
        $order->setData('base_subtotal_invoiced', 0);
        $order->setData('shipping_invoiced', 0);
        $order->setData('total_invoiced', 0);
        $order->setData('total_paid', 0);
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllItems() as $item) {
            $item->setQtyInvoiced(0);
            $item->save();
        }
    }
    
    
    /**
     * Save new token
     *
     * @param $responses
     * @param $customerId
     * @param $orderId
     * @param $quoteId
     * @return void
     */
    private function _saveToken($responses, $customerId, $orderId, $quoteId)
    {
        // Avoid saving because payment was placed with token
        if (!isset($responses['payment_token'])) {
            return;
        }
        
        $cardNumber = $responses['req_card_number'];
        $ccLastFour = "****-****-****-" . substr($cardNumber, -4);

        $storeId = $this->storeManager->getStore()->getId();
        if (isset($responses['reason_code']) && 100 == $responses['reason_code']) {
            $tokenInfo = [
                'created_date' => gmdate("Y-m-d\\TH:i:s\\Z"),
                'customer_id' => $customerId,
                'payment_token' => isset($responses['payment_token']) ? $responses['payment_token'] : '',
                'transaction_id' => isset($responses['transaction_id']) ? $responses['transaction_id'] : '',
                'store_id' => $storeId,
                'card_type' => isset($responses['req_card_type']) ? $responses['req_card_type'] : '',
                'updated_date' => gmdate("Y-m-d\\TH:i:s\\Z"),
                'cc_number' => $cardNumber,
                'cc_last4' => $ccLastFour,
                'card_expiry_date' => isset($responses['req_card_expiry_date']) ?
                    $responses['req_card_expiry_date'] :
                    '',
                'reference_number' => isset($responses['req_reference_number']) ?
                    $responses['req_reference_number'] :
                    '',
                'customer_email' => isset($responses['req_bill_to_email']) ?
                    $responses['req_bill_to_email'] :
                    '',
                'order_id' => $orderId,
                'quote_id' => $quoteId,
                'payment_type' => isset($responses['req_transaction_type']) ?
                    $responses['req_transaction_type']
                    : ''
            ];
            if (isset($responses['req_transaction_type']) &&
                'authorization,create_payment_token' == $responses['req_transaction_type']
            ) {
                $tokenInfo['authorize_only'] = 1;
            }
            $this->token->addData($tokenInfo);
            try {
                $this->token->save();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
    
    /**
     * Returns any possible status for state
     *
     * @param string $state
     * @return string
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    private function getStatusByState($state)
    {
        return $this->status->loadDefaultByState($state)->getStatus();
    }
    
    private function updateTransaction($transactionId, $order, $replaceWith)
    {
        $this->logger->info("update transaction " .$transactionId);
        $collection = $this->historyCollectionFactory->create();
        $collection->addFieldToFilter('parent_id', $order->getId());
        $history = $collection->getFirstItem();
        if ($history) {
            $this->logger->info("history comment " .$history->getComment());
            $comment = $history->getComment();
            $comment = str_replace('Captured', $replaceWith, $comment);
            if (preg_match('/amount of \$([0-9\.]+) online/', $comment, $match)) {
                $comment = str_replace($match[1], $order->getGrandTotal(), $comment);
            }
            $history->setComment($comment);
            $history->save();
        }
    }
}
