<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Cron;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;

class Dm
{

    const PAYPAL_METHOD = 'cybersourcepaypal';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

   /**
    * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
    */
    private $salesOrderCollectionFactory;


   /**
    * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
    */
    private $paymentCollectionFactory;

   /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    private $scopeConfig;

   /** @var \CyberSource\Core\Model\Payment
    *
    */
    private $cybersourcePayment;

   /** @var \CyberSource\Core\Service\CyberSourceSoapAPI
    *
    */
    private $cybersourceApi;

   /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    private $curl;

   /**
    * @var \Magento\Sales\Api\OrderRepositoryInterface
    */
    private $orderRepository;

   /**
    * @var string
    */
    private $token;
    
   /**
    * @var \Magento\Store\Model\StoreManagerInterface
    */
    private $storeManager;

    /**
     * @var  \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $invoiceService;
    
    /**
     *
     * @var string
     */
    private $newStatus;
    
    /**
     *
     * @var \Magento\Framework\Encryption\Encryptor
     */
    private $crypt;

    /**
     *
     * @var \Magento\Framework\DataObject
     */
    private $postObject;
    
    /**
     * @var  \CyberSource\Core\Helper\Data
     */
    private $helper;
    
    /**
     *
     * @var \CyberSource\Core\Model\ResourceModel\Token\Collection
     */
    private $tokenCollection;

    /**
     * @var \Magento\Sales\Model\Order\Status $status
     */
    private $status;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;
    
    /**
     * Dm constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory
     * @param \CyberSource\Core\Model\Payment $cybersourcePayment
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \CyberSource\Core\Service\CyberSourceSoapAPI $cybersourceApi
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \CyberSource\Core\Model\Token $token
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \CyberSource\Core\Helper\Data $helper
     * @param \Magento\Framework\DataObject $postObject
     * @param \CyberSource\Core\Model\ResourceModel\Token\Collection $tokenCollection
     * @param \Magento\Framework\Encryption\Encryptor $crypt
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \CyberSource\Core\Model\Payment $cybersourcePayment,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \CyberSource\Core\Service\CyberSourceSoapAPI $cybersourceApi,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \CyberSource\Core\Model\Token $token,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \CyberSource\Core\Helper\Data $helper,
        \Magento\Framework\DataObject $postObject,
        \CyberSource\Core\Model\ResourceModel\Token\Collection $tokenCollection,
        \Magento\Framework\Encryption\Encryptor $crypt,
        \Magento\Sales\Model\Order\Status $status,
        InvoiceRepository $invoiceRepository
    ) {
        $this->logger = $logger;
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->cybersourcePayment = $cybersourcePayment;
        $this->curl = $curl;
        $this->cybersourceApi = $cybersourceApi;
        $this->orderRepository = $orderRepository;
        $this->token = $token;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->helper = $helper;
        $this->invoiceService = $invoiceService;
        $this->postObject = $postObject;
        $this->tokenCollection = $tokenCollection;
        $this->crypt = $crypt;
        $this->status = $status;
        $this->invoiceRepository = $invoiceRepository;
    }
    
    public function sendEmail($order, $storeId)
    {
        $this->logger->info(__LINE__ . " " . __FUNCTION__);
        $emailTempVariables = ['order' => $order];
        $this->logger->info(__LINE__ . " " . __FUNCTION__);

        $sender = $this->scopeConfig->getValue(
            "payment/chcybersource/dm_fail_sender",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $this->logger->info(__LINE__ . " " . __FUNCTION__);

        $senderName = $this->scopeConfig->getValue(
            "trans_email/ident_".$sender."/name",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $this->logger->info(__LINE__ . " " . __FUNCTION__);

        $senderEmail = $this->scopeConfig->getValue(
            "trans_email/ident_".$sender."/email",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $this->logger->info(__LINE__ . " " . __FUNCTION__);

        $email = $order->getCustomerEmail();
        $this->logger->info(__LINE__ . " " . __FUNCTION__);
        $this->postObject->setData($emailTempVariables);
        $sender = [
            'name' => $senderName,
            'email' => $senderEmail,
        ];

        $this->logger->info(__LINE__ . " " . __FUNCTION__);

        $emailTemplate = $this->scopeConfig->getValue(
                "payment/chcybersource/dm_fail_template",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
                );
       
        $transport = $this->transportBuilder->setTemplateIdentifier($emailTemplate)
            ->setTemplateOptions([
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE => $storeId
        ])
        ->setTemplateVars(['data' => $this->postObject])
        ->setFrom($sender)
        ->addTo($email)
        ->setReplyTo($senderEmail)
        ->getTransport();
        $this->logger->info(__LINE__ . " " . __FUNCTION__);
        $transport->sendMessage();
        $this->logger->info(__LINE__ . " " . __FUNCTION__);
        $this->logger->info("cancel email sent from store id " . $storeId . " to " . $email);
    }
    
    public function execute()
    {
        foreach ($this->storeManager->getStores() as $storeId => $store) {
            $this->logger->info("store id = ".$storeId.' -> '.$store->getName());
            if (!$this->scopeConfig->getValue(
                "payment/chcybersource/enable_dm_cron",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store->getId()
            )) {
                continue; //we have to keep looking at other stores.
            }
            $this->logger->info(__METHOD__);
            $url = $this->scopeConfig->getValue(
                "payment/chcybersource/report_url",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store->getId()
            );
            if ($url === null) {
                continue;
            }
            $params = $this->composeParams($store->getId());
            $this->curl->post($url, $params);
            $response = $this->curl->getBody();
            $this->logger->info('response = '.$response);
            if (preg_match_all(
                '/<Conversion MerchantReferenceNumber="(\d+)" ConversionDate="([0-9-\s:]+)" RequestID="(\d+)">/',
                $response,
                $matches
            )) {
                $payment_info = $this->composePaymentInfo($response);

                $params['password'] = $this->crypt->decrypt($this->scopeConfig->getValue(
                    "payment/chcybersource/report_password", 
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $store->getId()
                ));

                foreach ($payment_info as $order_id => $info) {
                    $this->processOrder($order_id, $params, $payment_info, $store->getId());
                }
            }
        }
        return $this;
    }
    
    private function processOrder($order_id, $params, $payment_info, $storeId)
    {        
        $order = $this->orderRepository->get($order_id);
        $this->logger->info("order store id " . $order->getStoreId() . " script store id " . $storeId  );

        if ($order->getStoreId() != $storeId ) {
            //this order is placed on a different store, we'll process it later
            return;
        }

        $this->newStatus = null;
        if (!empty($payment_info[$order->getId()])) {
            $this->processSave($params, $payment_info, $order, $storeId);
        }
        $this->logger->info('step 3 new status = '.$this->newStatus);
        if (!empty($this->newStatus)) {
            $order
                ->setStatus($this->getStatusByState($this->newStatus))
                ->setState($this->newStatus)
                ->save();
            if ($this->newStatus == 'canceled') {
                $this->processCancel($payment_info, $order, $storeId);
            }
        }
    }
    
    private function processCancel($payment_info, $order, $storeId)
    {
        if ($payment_info[$order->getId()]) {
            $this->cybersourceApi->setPayment($payment_info[$order->getId()]['payment']);
            $this->cybersourceApi->reverseOrderPayment($storeId);
        }

        /** @var \Magento\Sales\Model\Order $order */
        $invoice = $order->getInvoiceCollection()->getFirstItem();

        /** @var \Magento\Sales\Api\Data\InvoiceInterface $invoice */
        $invoice->setState(Invoice::STATE_CANCELED);
        $this->invoiceRepository->save($invoice);

        $this->logger->info("send cancel email");
        $this->sendEmail($order, $storeId);
    }
    
    private function processSave($params, $payment_info, $order, $storeId)
    {
        $additional_information = $payment_info[$order->getId()]['payment']->getData('additional_information');

        $isTokenPaid = (!empty($additional_information['payment_token']));

        if ($isTokenPaid) {
            $this->logger->info("Paid by token");
        } else {
            $this->logger->info("Paid by new card");
        }

        $this->logger->info("type: ".$payment_info[$order->getId()]['type']);
        $skipStatuses = ['processing', 'canceled', 'closed'];
        if ($payment_info[$order->getId()]['type'] == 'capture') {
            if (!in_array($order->getState(), $skipStatuses)) {
                $this->saveCapture($params, $payment_info, $order, $isTokenPaid, $storeId);
            }
        } elseif ($payment_info[$order->getId()]['type'] == 'authorize'
            && !in_array($order->getState(), $skipStatuses)) {
            $this->saveAuthorize($params, $payment_info, $order, $isTokenPaid, $storeId);
        }
    }
    
    private function saveAuthorize($params, $payment_info, $order, $isTokenPaid, $storeId)
    {
        $this->newStatus = ($payment_info[$order->getId()]['NewDecision'] == 'ACCEPT') ? 'pending_payment' : 'canceled';

        if ($payment_info[$order->getId()]['NewDecision'] == 'ACCEPT') {
            //create offline invoice for settled payments on cybersource side
            $this->logger->info("settle = ".(int)$payment_info[$order->getId()]['settle']);
            if ($payment_info[$order->getId()]['settle']) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                $invoice->setShippingAmount($order->getData('shipping_amount'));
                $invoice->setSubtotal($order->getData('subtotal'));
                $invoice->setBaseSubtotal($order->getData('base_subtotal'));
                $invoice->setGrandTotal($order->getData('grand_total'));
                $invoice->setBaseGrandTotal($order->getData('base_grand_total'));
                $invoice->register()->save();
                $this->newStatus = 'processing';
            }

            $paymentMethod = $order->getPayment()->getMethod();

            if ($paymentMethod == self::PAYPAL_METHOD) {
                /** @var \Magento\Sales\Model\Order $order */
                $invoice = $order->getInvoiceCollection()->getFirstItem();

                /** @var \Magento\Sales\Model\Order\Invoice $invoice */
                if (!$invoice->isEmpty() && $invoice->canCapture()) {
                    $invoice->capture();
                    $this->invoiceRepository->save($invoice);
                }
            }

            $this->logger->info("order state = ".$order->getState());
            if ($order->getState() != 'pending_payment' && !$isTokenPaid && $paymentMethod != self::PAYPAL_METHOD) {
                $profile_data = [
                    'merchant_id' => $params['merchantID'],
                    'ref_id' => $order->getIncrementId(),
                    'request_id' => $payment_info[$order->getId()]['request_id']
                ];

                //create payment profile
                $result = $this->cybersourceApi->convertToProfile($profile_data, $storeId);

                $responses = [
                    'payment_token' => $result->paySubscriptionCreateReply->subscriptionID,
                    'reason_code' => $result->reasonCode,
                    'transaction_id' => $result->requestID,
                    'card_type' => $payment_info[$order->getId()]['payment']->getCcType(),
                    'card_expiry_date' => $payment_info[$order->getId()]['payment']->getCcExpMonth()
                        .'-'
                        .$payment_info[$order->getId()]['payment']->getCcExpYear(),
                    'reference_number' => $result->merchantReferenceCode,
                ];

                $this->tokenCollection->addFieldToFilter('order_id', $order->getId());
                $this->tokenCollection->load();

                if ($this->tokenCollection->getSize() == 0) {
                    $this->saveToken($responses, $order->getCustomerId(), $order->getId(), $order->getIncrementId(), $storeId);
                }
            }
        }
    }
    
    private function saveCapture($params, $payment_info, $order, $isTokenPaid, $storeId)
    {
        $this->newStatus = ($payment_info[$order->getId()]['NewDecision'] == 'ACCEPT') ? 'processing' : 'canceled';
        if ($payment_info[$order->getId()]['NewDecision'] == 'ACCEPT') {
            $this->logger->info($order->getId()." settle " . $payment_info[$order->getId()]['settle']);
            $transactionId = $order->getPayment()->getTransactionId();
            $this->logger->info("transaction id = ".$transactionId);

            /** @var \Magento\Sales\Model\Order $order */
            $invoice = $order->getInvoiceCollection()->getFirstItem();

            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            if (!$invoice->isEmpty() && $invoice->canCapture()) {
                $invoice->capture();

                if ($invoice->wasPayCalled()) {
                    /** @var \Magento\Sales\Model\Order\Item $item */
                    foreach ($order->getAllItems() as $item) {
                        $item->setQtyInvoiced($item->getQtyOrdered());
                        $item->save();
                    }
                    $this->invoiceRepository->save($invoice);
                }

                $this->logger->info("capture invoice state: ".$invoice->getState());
            }
        }
        if ($payment_info[$order->getId()]['NewDecision'] == 'ACCEPT' && !$isTokenPaid) {
            $profile_data = [
                    'merchant_id' => $params['merchantID'],
                    'ref_id' => $order->getIncrementId(),
                    'request_id' => $payment_info[$order->getId()]['request_id']
            ];
            /**
             * Create payment profile only when subscription reply
             * i.e. PayPal do not have this subscription node
             */

            $result = $this->cybersourceApi->convertToProfile($profile_data, $storeId);
            if (property_exists($result, 'paySubscriptionCreateReply')) {
                $responses = [
                    'payment_token' => $result->paySubscriptionCreateReply->subscriptionID,
                    'reason_code' => $result->reasonCode,
                    'transaction_id' => $result->requestID,
                    'card_type' => $payment_info[$order->getId()]['payment']->getCcType(),
                    'card_expiry_date' => $payment_info[$order->getId()]['payment']->getCcExpMonth()
                        .'-'.$payment_info[$order->getId()]['payment']->getCcExpYear(),
                    'reference_number' => $result->merchantReferenceCode,
                ];
                $this->saveToken($responses, $order->getCustomerId(), $order->getId(), $order->getIncrementId(), $storeId);
            }
        }
        $this->logger->info('step 2 new status = '.$this->newStatus);
    }
    
    private function composePaymentInfo($response)
    {
        $data = $this->parseResponse($response);
        
        $payment_info = [];
        
        foreach ($data as $cc_trans_id => $temp) {
            $paymentCollection = $this->paymentCollectionFactory->create();

            $paymentCollection->addFieldToFilter('cc_trans_id', $cc_trans_id);

            $paymentCollection->load();

            foreach ($paymentCollection as $payment) {
                $paid = $payment->getData('amount_paid');

                $payment_info[$payment->getParentId()] = [
                    'type' => (empty($paid)) ? 'authorize' : 'capture',
                    'NewDecision' => $data[$payment->getCcTransId()]['NewDecision'],
                    'amount' => $payment->getData('amount_authorized'),
                    'payment' => $payment,
                    'request_id' => $payment->getCcTransId(),
                    'settle' => (int)$data[$payment->getCcTransId()]['settle'],
                ];
                $this->logger->info("$cc_trans_id settle " . (int)$data[$payment->getCcTransId()]['settle']);
            }
        }
        
        return $payment_info;
    }

    private function parseResponse($response)
    {
        $xml = simplexml_load_string($response);
        $data = [];
        if (!empty($xml->Conversion)) {
            foreach ($xml->Conversion as $conversion) {
                $settle = false;
                foreach ($conversion->Notes->Note as $note) {
                    if (preg_match('/The card settlement succeeded/', (string)$note['Comment'])) {
                        $settle = true;
                    }
                }
                $data[(string)$conversion['RequestID']] = [
                    'OriginalDecision' => (string)$conversion->OriginalDecision,
                    'NewDecision' => (string)$conversion->NewDecision,
                    'settle' => $settle,
                ];
            }
        }
        return $data;
    }
    
    private function composeParams($storeId)
    {
        $params = [];

        $params['merchantID'] = $this->scopeConfig->getValue(
            "payment/chcybersource/merchant_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $this->logger->info(__LINE__ . " " . __FUNCTION__);
        $this->logger->info("merchant id " . $params['merchantID']);

        $params['username'] = $this->scopeConfig->getValue(
            "payment/chcybersource/report_username",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $params['password'] = $this->crypt->decrypt(
            $this->scopeConfig->getValue(
                "payment/chcybersource/report_password",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );

        $this->logger->info(__LINE__ . " " . __FUNCTION__);
        $this->logger->info("password id " . $params['password']);

        $start_ts = time()-23*3600;

        $end_ts = time();

        $params['startDate'] = gmdate('Y-m-d', $start_ts);

        $params['startTime'] = gmdate('H:i:s', $start_ts);

        $params['endDate'] = gmdate('Y-m-d', $end_ts);

        $params['endTime'] = gmdate('H:i:s', $end_ts);

        return $params;
    }
    
    private function saveToken($responses, $customerId, $orderId, $refId, $storeId)
    {
        // Avoid saving because payment was placed with token
        if (!isset($responses['payment_token'])) {
            return;
        }

        $profile = $this->cybersourceApi->retrieveProfile($responses['payment_token'], $refId, $storeId);
        if ($profile !== null && $profile->reasonCode === 100) {
            $cardNumber = "****-****-****-" . substr($profile->paySubscriptionRetrieveReply->cardAccountNumber, -4);
        }

        if (isset($responses['reason_code']) && 100 == $responses['reason_code']) {
            $tokenInfo = [
                'created_date' => gmdate("Y-m-d\\TH:i:s\\Z"),
                'customer_id' => $customerId,
                'payment_token' => isset($responses['payment_token']) ? $responses['payment_token'] : '',
                'transaction_id' => isset($responses['transaction_id']) ? $responses['transaction_id'] : '',
                'store_id' => $storeId,
                'card_type' => isset($responses['card_type']) ? $responses['card_type'] : '',
                'updated_date' => gmdate("Y-m-d\\TH:i:s\\Z"),
                'cc_number' => $profile->paySubscriptionRetrieveReply->cardAccountNumber,
                'cc_last4' => isset($cardNumber) ? $cardNumber : '',
                'card_expiry_date' => isset($responses['card_expiry_date']) ? $responses['card_expiry_date'] : '',
                'reference_number' => isset($responses['reference_number']) ? $responses['reference_number'] : '',
                'customer_email' => isset($responses['req_bill_to_email']) ? $responses['req_bill_to_email'] : '',
                'order_id' => $orderId,
                'quote_id' => $refId,
                'payment_type' => isset($responses['req_transaction_type']) ? $responses['req_transaction_type'] : ''
            ];
            if (isset($responses['req_transaction_type'])
                && preg_match('/authorization/', $responses['req_transaction_type'])) {
                $tokenInfo['authorize_only'] = 1;
            }
            try {
                $this->token->addData($tokenInfo);
                $this->token->save();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
            $this->logger->info("after save end");
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
}
