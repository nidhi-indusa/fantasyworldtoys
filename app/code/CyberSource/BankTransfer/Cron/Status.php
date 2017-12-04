<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\BankTransfer\Cron;

class Status
{

    protected $_logger;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $salesOrderCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
     */
    protected $paymentCollectionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;


    /** @var Collinsharper\Cybersource\Model\Payment
     *
     */
    protected $cybersourcePayment;


    /** @var Collinsharper\Cybersource\Service\CyberSourceAPI
     *
     */
    protected $cybersourceApi;


    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;


    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;


    /**
     * @var
     */
    protected $_token;


    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;


    /**
     * @var  \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $_transportBuilder;


    /**
     *
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;
    
    
    /**
     * @var  \CyberSource\Core\Helper\Data
     */
    private $_helper;
    
    /**
     * @var  \Magento\Framework\DB\Transaction
     */
    private $_transaction;


    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \CyberSource\SecureAcceptance\Model\Payment $cybersourcePayment,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \CyberSource\Core\Service\CyberSourceSoapAPI $cybersourceApi,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \CyberSource\SecureAcceptance\Model\Token $token,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \CyberSource\Core\Helper\Data $helper,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        $this->_logger = $logger;
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->cybersourcePayment = $cybersourcePayment;
        $this->curl = $curl;
        $this->cybersourceApi = $cybersourceApi;
        $this->orderRepository = $orderRepository;
        $this->_token = $token;
        $this->_storeManager = $storeManager;
        $this->_transportBuilder = $transportBuilder;
        $this->_helper = $helper;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
    }
    
    
    public function execute()
    {
        if ($this->isActive()) {
            $this->_logger->info(__METHOD__);
            try {
                $paymentCollection = $this->paymentCollectionFactory->create();
                $paymentCollection->addFieldToFilter('main_table.method', 'cybersource_bank_transfer');
                $paymentCollection->addFieldToFilter('order_table.status', 'pending_payment');
                $paymentCollection->getSelect()->joinleft(
                    ['order_table' => $paymentCollection->getTable('sales_order')],
                    'main_table.parent_id = order_table.entity_id',
                    ['status', 'quote_id']
                );
                $paymentCollection->load();
                foreach ($paymentCollection as $payment) {
                    if (!empty($payment->getData('last_trans_id'))) {
                        $paymentMethod = $payment->getAdditionalInformation('payment_method');
                        $this->_logger->info("this payment id " . $payment->getId());
                        $this->_logger->info("payment method = ".$payment->getAdditionalInformation('payment_method'));
                        $result = $this->cybersourceApi->checkBankTransferStatus(
                            $this->_scopeConfig->getValue("payment/cybersource_bank_transfer/".$paymentMethod."_merchant_id", \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                            $payment->getData('order_id'),
                            $payment->getData('last_trans_id'),
                            $paymentMethod
                        );
                        $this->updateOrder($result, $payment->getOrder());
                    }
                }
            } catch (\Exception $e) {
                $this->_logger->info("error: ".$e->getMessage());
            }
        }
        return $this;
    }
    
    private function updateOrder($result, $order)
    {
        if (!empty($result)
                && !empty($result->apCheckStatusReply)
                && $result->apCheckStatusReply->paymentStatus == 'settled') {
            $this->_logger->info("settle order start #".$order->getId());
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $this->orderRepository->save($order);
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            $invoice->setTransactionId($result->apCheckStatusReply->reconciliationID);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->_transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
            $this->_logger->info("settle order end");
        }
    }
    
    private function isActive()
    {
        return (bool)(int)$this->_scopeConfig->getValue(
            "payment/cybersource_bank_transfer/bancontact_active",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )
            || (bool)(int)$this->_scopeConfig->getValue(
                "payment/cybersource_bank_transfer/sofort_active",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            || (bool)(int)$this->_scopeConfig->getValue(
                "payment/cybersource_bank_transfer/ideal_active",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }
}
