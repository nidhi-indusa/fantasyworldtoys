<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Cron;

use CyberSource\ECheck\Gateway\Command\CaptureCaller;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Sales\Model\Order;

class Status extends CaptureCaller
{
    const EVENT_TYPE_COULMN = 4;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Magento\Payment\Gateway\Http\TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var \CyberSource\ECheck\Gateway\Request\ReportRequest
     */
    private $request;

    /**
     * @var \CyberSource\ECheck\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \CyberSource\ECheck\Gateway\Http\Client\HTTPClient
     */
    private $client;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
     */
    protected $paymentCollectionFactory;

   /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    private $scopeConfig;
    
    /**
     *
     * @var \Magento\Framework\DataObject
     */
    private $postObject;
    
    /**
     * @var  \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;
    
   /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    private $curl;
    
    /**
     *
     * @var \Magento\Framework\Encryption\Encryptor
     */
    private $crypt;
    
    /**
     * Status constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        CommandPoolInterface $commandPool,
        \CyberSource\ECheck\Gateway\Http\Client\HTTPClient $client,
        \CyberSource\ECheck\Gateway\Request\ReportRequest $request,
        \Magento\Payment\Gateway\Http\TransferBuilder $transferBuilder,
        \CyberSource\ECheck\Gateway\Config\Config $config,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\DataObject $postObject,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Encryption\Encryptor $crypt
    ) {
        $this->orderRepository = $orderRepository;
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
        $this->client = $client;
        $this->request = $request;
        $this->logger = $logger;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->postObject = $postObject;
        $this->transportBuilder = $transportBuilder;
        $this->curl = $curl;
        $this->crypt = $crypt;
        parent::__construct($commandPool);
    }

    public function execute()
    {
        if ((bool)(int)$this->scopeConfig->getValue(
            "payment/cybersourceecheck/active",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            $this->logger->info("start echeck status");
            try {
                $reportData = $this->getReportData();
                $paymentCollection = $this->paymentCollectionFactory->create();
                $paymentCollection->getSelect()->joinleft(
                    ['order_table' => $paymentCollection->getTable('sales_order')],
                    'main_table.parent_id = order_table.entity_id',
                    ['status', 'quote_id']
                );
                $this->logger->info(print_r($reportData, 1));
                $paymentCollection->addFieldToFilter('main_table.last_trans_id', ['in' => array_keys($reportData)]);
                $paymentCollection->load();
                foreach ($paymentCollection as $payment) {
                    $this->updateOrder($reportData[$payment->getLastTransId()][self::EVENT_TYPE_COULMN], $payment->getOrder());
                    $this->logger->info('order #'.$payment->getOrder()->getId().' gets event type '.$reportData[$payment->getLastTransId()][self::EVENT_TYPE_COULMN]);
                }
            } catch (\Exception $e) {
                $this->logger->info("error: ".$e->getMessage());
            }
        }
        return $this;
    }
    
    private function updateOrder($eventType, $order)
    {
        $updateStatus = true;
        if (!in_array($eventType, $this->config->getAcceptEventType())
            && !in_array($eventType, $this->config->getRejectEventType())
            && !in_array($eventType, $this->config->getPendingEventType())
        ) {
            $this->logger->info("unknown event type");
            $this->sendEmail($order, $eventType, 'cybersource_echeck_unknown');
            $updateStatus = false;
        }
        $inCounter = 0;
        if ($updateStatus && in_array($eventType, $this->config->getAcceptEventType())) {
            $inCounter++;
        }
        if ($updateStatus && in_array($eventType, $this->config->getRejectEventType())) {
            $inCounter++;
        }
        if ($updateStatus && $inCounter > 1) {
            $this->logger->info("multi event type");
            $this->sendEmail($order, $eventType, 'cybersource_echeck_multi');
            $updateStatus = false;
        }
        if ($updateStatus && in_array($eventType, $this->config->getAcceptEventType())) {
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus(Order::STATE_PROCESSING);
            $this->orderRepository->save($order);
        }
        if ($updateStatus && in_array($eventType, $this->config->getRejectEventType())) {
            $order->setState(Order::STATE_CANCELED);
            $order->setStatus(Order::STATE_CANCELED);
            $this->orderRepository->save($order);
        }
    }
    
    public function sendEmail($order, $eventType, $templateId = 'cybersource_echeck_unknown')
    {
        $this->logger->info("start echeck email");
        $this->logger->info("email template ".$templateId);
        $emailTemplateVariables = [];
        $emailTempVariables = ['order' => $order, 'event_type' => $eventType];
        $sender = $this->scopeConfig->getValue(
            "payment/chcybersource/dm_fail_sender",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $senderName = $this->scopeConfig->getValue(
            "trans_email/ident_".$sender."/name",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $senderEmail = $this->scopeConfig->getValue(
            "trans_email/ident_".$sender."/email",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $email = $this->scopeConfig->getValue(
            "trans_email/ident_general/email",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $this->postObject->setData($emailTempVariables);
        $sender = [
            'name' => $senderName,
            'email' => $senderEmail,
        ];
        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
        ->setTemplateOptions([
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
        ])
        ->setTemplateVars(['data' => $this->postObject])
        ->setFrom($sender)
        ->addTo($email)
        ->setReplyTo($senderEmail)
        ->getTransport();
        $transport->sendMessage();
        $this->logger->info("end echeck email");
    }
    
    private function getReportData()
    {
        $this->logger->info("start get report");
        $data = [];
        if ($this->scopeConfig->getValue(
            "payment/chcybersource/test_mode",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            $this->logger->info("get test data");
            $paymentCollection = $this->paymentCollectionFactory->create();
            $paymentCollection->addFieldToFilter('main_table.method', 'cybersourceecheck');
            $paymentCollection->addFieldToFilter('order_table.status', 'pending_payment');
            $paymentCollection->getSelect()->joinleft(
                ['order_table' => $paymentCollection->getTable('sales_order')],
                'main_table.parent_id = order_table.entity_id',
                ['status', 'quote_id']
            );
            $paymentCollection->load();
            foreach ($paymentCollection as $payment) {
                $data[$payment->getLastTransId()] = [
                    0 => $payment->getLastTransId(),
                    self::EVENT_TYPE_COULMN => $this->scopeConfig->getValue(
                        "payment/cybersourceecheck/test_event_type",
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                ];
            }
        } else {
            $reportUrl = $url = $this->scopeConfig->getValue(
                "payment/chcybersource/report_server_url",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $username = $this->scopeConfig->getValue(
                "payment/chcybersource/report_username",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $password = $this->crypt->decrypt(
                $this->scopeConfig->getValue(
                    "payment/chcybersource/report_password",
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            );
            $period = $this->scopeConfig->getValue(
                "payment/cybersourceecheck/report_check_period",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $this->curl->setCredentials($username, $password);
            for ($i = 1; $i < $period; $i++) {
                $this->curl->get($reportUrl.'/DownloadReport/'.date('Y/m/d', strtotime('-'.$i.' day')).'/chtest/PaymentEventsReport.csv');
                $data = $this->parseCsvFile($this->curl->getBody(), $data);
            }
        }
        return $data;
    }
    
    /**
     * Expected CSV format
     * 0 request_id,
     * 1 merchant_id,
     * 2 merchant_ref_number,
     * 3 payment_type,event_type,
     * 4 event_date,
     * 5 trans_ref_no,
     * 6 merchant_currency_code,
     * 7 merchant_amount,
     * 8 consumer_currency_code,
     * 9 consumer_amount,
     * 10 fee_currency_code,
     * 11 fee_amount,processor_message
     *
     * @param type $response
     * @param type $data
     * @return type
     */
    private function parseCsvFile($response, $data)
    {
        if (preg_match('/^Payment Events Report/', $response)) {
            $this->logger->info("before explode");
            $lines = explode("\n", $response);
            for ($j = 2; $j < count($lines); $j++) {
                if (!empty($lines[$j])) {
                    $fileData = explode(",", $lines[$j]);
                    $data[$fileData[0]] = $fileData;
                }
            }
        }
        return $data;
    }
}
