<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Service;

use CyberSource\Core\Helper\RequestDataBuilder;
use CyberSource\Core\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class CyberSourceSoapAPI extends AbstractConnection
{
    const SUCCESS_REASON_CODE = 100;

    /** @var  \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface */
    private $transactionBuilder;

    /**
     * @var \SoapClient
     */
    public $client;
   
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;
    
    /**
     * @var int
     */
    private $merchantReferenceCode;

    /**
     * @var RequestDataBuilder
     */
    protected $requestDataHelper;
    
    /**
     * @var bool $firstAttempt
     */
    private $firstAttempt = true;

    /**
     * @var \Magento\Backend\Model\Auth\Session $session
     */
    private $session;

    /**
     * @var \Magento\Sales\Model\Order\Payment
     */
    private $payment = null;

    /**
     * @var bool
     */
    private $isSuccessfullyVoid = false;

    /**
     * @var bool
     */
    private $isSuccessfullyReverse = false;
    
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Directory\Model\Region
     */
    private $regionModel;

    /**
     * @var Config
     */
    private $gatewayConfig;
    
    /**
     * CyberSourceSoapAPI constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $gatewayConfig
     * @param LoggerInterface $logger
     * @param BuilderInterface $transactionBuilder
     * @param RequestDataBuilder $requestDataHelper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \SoapClient $client
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $gatewayConfig,
        LoggerInterface $logger,
        BuilderInterface $transactionBuilder,
        RequestDataBuilder $requestDataHelper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Directory\Model\Region $regionModel,
        \SoapClient $client = null
    ) {
        parent::__construct($scopeConfig, $logger);

        /**
         * Added soap client as parameter to be able to mock in unit tests.
         */
        if ($client !== null) {
            $this->setSoapClient($client);
        }

        $this->gatewayConfig = $gatewayConfig;

        $this->client = $this->getSoapClient();
        $this->transactionBuilder = $transactionBuilder;
        $this->requestDataHelper = $requestDataHelper;
        $this->curl = $curl;
        $this->session = $authSession;
        $this->regionModel = $regionModel;
    }

    /**
     * @param $payment
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
        $this->merchantReferenceCode = $payment->getOrder()->getIncrementId();
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->payment->getAmountAuthorized();
    }

    /**
     * Get merchant reference code
     *
     * @return int|null
     */
    public function getMerchantReferenceCode()
    {
        return $this->merchantReferenceCode;
    }

    /**
     * Build capture request
     *
     * @param $amount
     * @return \stdClass $result
     * @throws \Exception
     */
    public function captureOrder($amount)
    {
        $request = new \stdClass();        
        $request->partnerSolutionID = 'T54H9OLO';
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $this->getMerchantReferenceCode();

        $requestToken = $this->payment->getCcTransId();

        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = "true";
        $ccCaptureService->authRequestID = $requestToken;

        $request = $this->buildOrderItems($this->payment->getOrder()->getAllItems(), $request);

        $request->ccCaptureService = $ccCaptureService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $this->payment->getOrder()->getOrderCurrencyCode();
        $amount = $this->requestDataHelper->formatAmount($amount);
        $purchaseTotals->grandTotalAmount = $amount;
        $request->purchaseTotals = $purchaseTotals;

        $result = null;
        try {
            $this->logger->info('capture request: '.print_r($request, 1));
            $this->logger->info('capture for store: '.$this->payment->getOrder()->getStoreId());
            $this->setCredentialsByStore($this->payment->getOrder()->getStoreId());
            $this->initSoapClient();
            $request->merchantID = $this->merchantId;
            $result = $this->client->runTransaction($request);
            $this->logger->info('capture response: '.print_r($result, 1));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $result;
    }

    private function buildOrderItems($items, \stdClass $request)
    {
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $i => $item) {
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $item->getQtyOrdered();
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->requestDataHelper->formatAmount($item->getPrice());
            $requestItem->taxAmount = $this->requestDataHelper->formatAmount($item->getTaxAmount());
            $request->item[] = $requestItem;
        }

        foreach ($request->item as $key => $item) {
            if ($item->unitPrice == 0) {
                unset($request->item[$key]);
            }
        }

        $request->item = array_values($request->item);

        return $request;
    }

    /**
     * @param $tokenData
     * @param bool $dmEnabled
     * @param bool $isCaptureRequest
     * @param null $quote
     * @param null $amount
     * @param \Magento\Sales\Model\Order $order
     * @return \stdClass
     * @throws LocalizedException
     */
    public function tokenPayment(
        $tokenData,
        $dmEnabled = true,
        $isCaptureRequest = false,
        $quote = null,
        $amount = null,
        $order = null,
        $isAuthorizedPayment = false
    ) {

        if ($order !== null) {
            $this->logger->info("build request from order");
            $request = $this->requestDataHelper->buildTokenPaymentDataFromOrder(
                $tokenData,
                $order,
                $this->session->isLoggedIn(),
                $amount,
                $dmEnabled,
                $isCaptureRequest,
                $isAuthorizedPayment
            );
            $storeId = $order->getStoreId();
        } else {
            $this->logger->info("build request from order");
            $request = $this->requestDataHelper->buildTokenPaymentData(
                $tokenData,
                $quote,
                $this->session->isLoggedIn(),
                $amount,
                $dmEnabled,
                $isCaptureRequest
            );
            $storeId = $tokenData['store_id'];
        }
        $response = null;
        try {
            $this->setCredentialsByStore($storeId);
            $this->initSoapClient();
            $this->logger->info("token payment request = ".print_r($request, 1));
            $response = $this->client->runTransaction($request);
            $this->logger->info("token payment response = ".print_r($response, 1));
            if ($response->reasonCode !== self::SUCCESS_REASON_CODE &&
                $response->decision != 'REVIEW') {
                throw new LocalizedException(
                    $this->requestDataHelper->wrapGatewayError(
                        "Unable to place order"
                    )
                );
            }
        } catch (\SoapFault $soapFault) {
            $this->logger->error($soapFault->getMessage());
        }

        return $response;
    }

    /**
     * Build retrieve profile request
     *
     * @param string $subscriptionId
     * @return \stdClass
     * @throws \Exception
     */
    public function retrieveProfile($subscriptionId, $merchantReferenceCode, $storeId)
    {
        $request = new \stdClass();
        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $merchantReferenceCode;

        $recurringSubscriptionInfo = new \stdClass();
        $recurringSubscriptionInfo->subscriptionID = $subscriptionId;

        $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

        $paySubscriptionRetrieveService = new \stdClass();
        $paySubscriptionRetrieveService->run = "true";

        $request->paySubscriptionRetrieveService = $paySubscriptionRetrieveService;

        $response = null;
        try {
            $this->setCredentialsByStore($storeId);
            $this->initSoapClient();
            $response = $this->client->runTransaction($request);
        } catch (\SoapFault $soapFault) {
            $this->logger->error($soapFault->getMessage());
            throw new LocalizedException(__($soapFault->getMessage()));
        }

        return $response;
    }
    
    /**
     * Create profile from transaction
     *
     * @param array $data
     * @param int $storeId
     * @return \stdClass
     */
    public function convertToProfile($data, $storeId = null)
    {
        $request = $this->requestDataHelper->buildTokenByTransaction($data);
        $result = null;
        try {
            if (!empty($storeId)) {
                $this->setCredentialsByStore($storeId);
                $this->initSoapClient();
            }
            $result = $this->client->runTransaction($request);
        } catch (\Exception $e) {
            $this->logger->error("convert error: " . $e->getMessage());
        }
        return $result;
    }

    /**
     * Reverse authorized payment at Cybersource
     *
     * @return \stdClass
     * @throws \Exception
     */
    public function reverseOrderPayment($storeId)
    {
        $request = new \stdClass();        
        $request->partnerSolutionID = 'T54H9OLO';
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $this->getMerchantReferenceCode();

        $requestToken = $this->payment->getCcTransId();

        $ccAuthReversalService = new \stdClass();
        $ccAuthReversalService->run = "true";
        $ccAuthReversalService->authRequestID = $requestToken;
        $request->ccAuthReversalService = $ccAuthReversalService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $this->payment->getOrder()->getOrderCurrencyCode();
        $amount = $this->requestDataHelper->formatAmount($this->payment->getOrder()->getGrandTotal());
        $purchaseTotals->grandTotalAmount =  $amount;
        $request->purchaseTotals = $purchaseTotals;

        $result = null;
        try {
            $this->setCredentialsByStore($storeId);
            $this->initSoapClient();
            $request->merchantID = $this->merchantId;
            $this->logger->info("reverse request ".print_r($request, 1));
            $result = $this->client->runTransaction($request);
            $this->logger->info("reverse response ".print_r($result, 1));
            $transaction = $this->buildTransaction(
                $result,
                \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND
            );
            if (100 == $result->reasonCode) {
                $this->isSuccessfullyReverse = true;
                $this->payment->addTransactionCommentsToOrder($transaction, "Successfully reverse");
            } elseif (in_array($result->reasonCode, [150, 151, 152])) {
                $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $storeId);
                if ($status == 'error' && $this->firstAttempt) {
                    $this->firstAttempt = false;
                    $this->reverseOrderPayment($storeId);
                }
            } else {
                $this->payment->addTransactionCommentsToOrder($transaction, "Unable to reverse");
                throw new LocalizedException($this->requestDataHelper->wrapGatewayError("Unable to reverse payment"));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $storeId);
            if ($status == 'error' && $this->firstAttempt) {
                $this->firstAttempt = false;
                $this->reverseOrderPayment($storeId);
            }
        }
        return $result;
    }

    /**
     * Cancel payment and attach transaction info
     *
     * @return \stdClass
     */
    public function voidOrderPayment($storeId)
    {
        $request = new \stdClass();        
        $request->partnerSolutionID = 'T54H9OLO';
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $this->getMerchantReferenceCode();

        $voidService = new \stdClass();
        $voidService->run = "true";
        $voidService->voidRequestID = $this->payment->getLastTransId();

        $request->voidService = $voidService;

        $result = null;
        try {
            $this->setCredentialsByStore($storeId);
            $this->initSoapClient();
            $request->merchantID = $this->merchantId;
            $result = $this->client->runTransaction($request);
            $transaction = $this->buildTransaction($result, \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID);
            if (100 == $result->reasonCode) {
                $this->isSuccessfullyVoid = true;
                $this->payment->addTransactionCommentsToOrder($transaction, "Successfully void");
            } elseif (in_array($result->reasonCode, [150, 151, 152])) {
                $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $storeId);
                if ($status == 'error' && $this->firstAttempt) {
                    $this->firstAttempt = false;
                    $this->reverseOrderPayment($storeId);
                }
            } else {
                $this->payment->addTransactionCommentsToOrder($transaction, "Unable to void payment");
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $storeId);
            if ($status == 'error' && $this->firstAttempt) {
                $this->firstAttempt = false;
                $this->voidOrderPayment($storeId);
            }
        }
        return $result;
    }

    /**
     * Refund a captured order
     *
     * @param float $amount
     * @return bool
     */
    public function refundOrderPayment($amount)
    {
        $request = new \stdClass();
        $request->partnerSolutionID = 'T54H9OLO';
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $this->getMerchantReferenceCode();

        $ccCreditService = new \stdClass();
        $ccCreditService->run = "true";
        $ccCreditService->captureRequestID = $this->payment->getCcTransId();
        $request->ccCreditService = $ccCreditService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $this->payment->getOrder()->getOrderCurrencyCode();
        $purchaseTotals->grandTotalAmount =  $this->requestDataHelper->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $order = $this->payment->getOrder();
        foreach ($order->getAllVisibleItems() as $i => $item) {
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $item->getQtyOrdered();
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $item->getPrice();
            $requestItem->taxAmount = $item->getTaxAmount();
            $request->item[] = $requestItem;
        }

        $result = null;
        $success = false;
        try {
            $this->setCredentialsByStore($this->payment->getOrder()->getStoreId());
            $this->initSoapClient();
            $request->merchantID = $this->merchantId;
            $this->logger->info('store id = '.$this->payment->getOrder()->getStoreId());
            $this->logger->info('refund request = '.print_r($request, 1));
            $result = $this->client->runTransaction($request);
            $this->logger->info('refund response = '.print_r($result, 1));
            if (100 == $result->reasonCode) {
                $transaction = $this->buildTransaction(
                    $result,
                    \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND
                );
                $this->payment->addTransactionCommentsToOrder($transaction, "Successfully refund");
                $success = true;
            } elseif (in_array($result->reasonCode, [150, 151, 152])) {
                $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $this->payment->getOrder()->getStoreId());
                if ($status == 'error' && $this->firstAttempt) {
                    $this->firstAttempt = false;
                    $this->reverseOrderPayment($this->payment->getOrder()->getStoreId());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('refund error = '.$e->getMessage());
            $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $this->payment->getOrder()->getStoreId());
            if ($status == 'error' && $this->firstAttempt) {
                $this->firstAttempt = false;
                $this->refundOrderPayment($amount);
            }
        }
        $this->logger->info("REFUND REQUEST:\n" . $this->client->__getLastRequest());
        return $success;
    }

    /**
     * @return bool
     */
    public function isSuccessfullyVoided()
    {
        return $this->isSuccessfullyVoid;
    }

    /**
     * @return bool
     */
    public function isSuccessfullyReversed()
    {
        return $this->isSuccessfullyReverse;
    }

    /**
     * Build transaction object
     *
     * @param \stdClass $result
     * @param $type
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    private function buildTransaction(\stdClass $result, $type)
    {
        $trans = $this->transactionBuilder;

        $resultData = [
            "merchantReferenceCode" => $result->merchantReferenceCode,
            "requestID" => $result->requestID,
            "decision" => $result->decision,
            "reasonCode" => $result->reasonCode
        ];

        $transaction = $trans->setPayment($this->payment);

        $transaction->setOrder($this->payment->getOrder());
        $transaction->setTransactionId($result->requestID);
        $transaction->setAdditionalInformation(
            [
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => $resultData
            ]
        );
        $transaction->setFailSafe(true);
        $transactionBuilt = $transaction->build($type);

        return $transactionBuilt;
    }

    /**
     * Get On-Demand Single Transaction Report
     * @param int $quote_id
     * @param string $date, format yyyymmdd
     * @return string $status
     */
    public function getTransactionStatus($quote_id, $date, $storeId)
    {
        $url = $this->config->getValue(
            "payment/chcybersource/one_doc_report_url",
            'store',
            $storeId
        );

        $params = [];
        $params['merchantID'] = $this->config->getValue(
            "payment/chcybersource/merchant_id",
            'store',
            $storeId
        );
        $params['type'] = 'transaction';
        $params['subtype'] = 'transactionDetail';
        $params['merchantReferenceNumber'] = $quote_id;
        $params['targetDate'] = $date;
        $params['versionNumber'] = '1.5';

        $this->curl->setCredentials(
            $this->config->getValue(
                "payment/chcybersource/report_username",
                'store',
                $storeId
            ),
            $this->config->getValue(
                "payment/chcybersource/report_password",
                'store',
                $storeId
            )
        );

        $this->curl->post($url, $params);
        $status = 'error';

        try {
            $response = $this->curl->getBody();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        if (!empty($response)) {
            $xml = simplexml_load_string($response);
            if (!empty($xml->Requests->Request)) {
                $status = 'ok';
            }
        }
        return $status;
    }
    
    public function getListOfBanks($quoteId, $merchantId)
    {
        $this->setBankTransferCredentials();
        $data = [];
        $request = [];
        $request['apOptionsService'] = ['run' => 'true'];
        $request['merchantID'] = $merchantId;
        $request['merchantReferenceCode'] = $quoteId;
        $request['apPaymentType'] = 'IDL';
        $this->logger->info(print_r($request, 1));
        try {
            $this->initSoapClient();
            $result = $this->client->runTransaction(json_decode(json_encode($request)));
            $this->logger->info("get list of banks");
            $this->logger->info(print_r($result, 1));

            if ($result->reasonCode == 100) {
                foreach ($result->apOptionsReply->option as $opt) {
                    $data[$opt->id] = $opt->name;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("bank list: " . $e->getMessage());
        }
        $this->logger->info("REQUEST:\n" . $this->client->__getLastRequest());
        return $data;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $merchantId
     * @param $store
     * @param $bankCode
     * @param null $deviceId
     * @return array
     */
    public function bankTransferSale($quote, $merchantId, $store, $bankCode, $deviceId = null)
    {
        //for iDeal only
        $paymentMethod = (in_array($bankCode, ['sofort', 'bancontact'])) ? $bankCode : 'ideal';
  
        $this->setBankTransferCredentials($paymentMethod);

        $request = new \stdClass();
        
        $apSaleService = new \stdClass();
        $apSaleService->run = 'true';
        $apSaleService->cancelURL = $store->getBaseUrl() . 'cybersourcebt/index/cancel';
        $apSaleService->successURL = $store->getBaseUrl() . 'cybersourcebt/index/success';
        $apSaleService->failureURL = $store->getBaseUrl() . 'cybersourcebt/index/failure';
        
        $request->merchantID = $merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $quote->getReservedOrderId();
        
        switch ($bankCode) {
            case 'sofort':
                $request->apPaymentType = 'SOF';
                break;
            case 'bancontact':
                $request->apPaymentType = 'MCH';
                break;
            default:
                $request->apPaymentType = 'IDL';
                $apSaleService->paymentOptionID = $bankCode;
        }
        
        $request->apSaleService = $apSaleService;
        
        $purchaseTotals = new \stdClass();
        $purchaseTotals->grandTotalAmount = $quote->getGrandTotal();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $request->purchaseTotals = $purchaseTotals;
        
        $invoiceHeader = new \stdClass();
        $invoiceHeader->merchantDescriptor = 'Store Name';
        $request->invoiceHeader = $invoiceHeader;

        $billTo = new \stdClass();
        $billTo->country = $quote->getBillingAddress()->getCountry();
        if (in_array($billTo->country, ['CA', 'US'])) {
            $billTo->state = $quote->getBillingAddress()->getRegionCode();
        }
        $billTo->postalCode = $quote->getBillingAddress()->getPostcode();
        $billTo->firstName = $quote->getBillingAddress()->getFirstname();
        $billTo->lastName = $quote->getBillingAddress()->getLastname();
        $street = $quote->getBillingAddress()->getStreet(1);
        $billTo->street1 = $street[0];
        $billTo->city = $quote->getBillingAddress()->getCity();
        $billTo->phoneNumber = $quote->getBillingAddress()->getTelephone();
        $billTo->email = $quote->getBillingAddress()->getEmail();
        $request->billTo = $billTo;
        if (!empty($deviceId)) {
            $request->deviceFingerprintID = $deviceId;
        }
        $this->logger->info(print_r($request, 1));

        $data = [];

        try {
            $this->initSoapClient();
            $result = $this->client->runTransaction($request);
            $this->logger->info("get list of banks");
            $this->logger->info(print_r($result, 1));

            if (!empty($result) && $result->reasonCode == 100) {
                $data['redirect_url'] = $result->apSaleReply->merchantURL;
                $data['response'] = $result;
            } else {
                $data['redirect_url'] = $store->getBaseUrl() . 'cybersourcebt/index/failure';
            }
        } catch (\Exception $e) {
            $this->logger->error("bank transfer sale: " . $e->getMessage());
        }
        $this->logger->info("REQUEST:\n" . $this->client->__getLastRequest());
        return $data;
    }

    /**
     * @param $merchantId
     * @param $orderId
     * @param $requestId
     * @param $paymentMethod
     * @return null
     */
    public function checkBankTransferStatus($merchantId, $orderId, $requestId, $paymentMethod)
    {
        $this->setBankTransferCredentials($paymentMethod);
        
        $request = new \stdClass();
        $request->merchantID = $merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $orderId;
        switch ($paymentMethod) {
            case 'sofort':
                $request->apPaymentType = 'SOF';
                break;
            case 'bancontact':
                $request->apPaymentType = 'MCH';
                break;
            default:
                $request->apPaymentType = 'IDL';
        }
        $apCheckStatusService = new \stdClass();
        $apCheckStatusService->run = 'true';
        $apCheckStatusService->checkStatusRequestID = $requestId;
        $request->apCheckStatusService = $apCheckStatusService;
        
        $this->logger->info("check BT status request: ".print_r($request, 1));
        $result = null;
        try {
            $this->initSoapClient();
            $result = $this->client->runTransaction($request);
            $this->logger->info("check BT status response: ".print_r($result, 1));
        } catch (\Exception $e) {
            $this->logger->error("check bank transfer status: " . $e->getMessage());
        }
        return $result;
    }

    public function bankTransferRefund($order, $merchantId, $requestId, $paymentMethod)
    {
        $this->logger->info("API BT refund start");
        $this->setBankTransferCredentials($paymentMethod);
        
        $request = new \stdClass();
        $request->merchantID = $merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $order->getOrderIncrementId();
        switch ($paymentMethod) {
            case 'sofort':
                $request->apPaymentType = 'SOF';
                break;
            case 'bancontact':
                $request->apPaymentType = 'MCH';
                break;
            default:
                $request->apPaymentType = 'IDL';
        }
        
        $purchaseTotals = new \stdClass();
        $purchaseTotals->grandTotalAmount = $order->getGrandTotal();
        $purchaseTotals->currency = $order->getOrderCurrencyCode();
        $request->purchaseTotals = $purchaseTotals;
        
        $apRefundService = new \stdClass();
        $apRefundService->run = 'true';
        $apRefundService->refundRequestID = $requestId;
        $request->apRefundService = $apRefundService;
        
        $this->logger->info("BT refund request: ".print_r($request, 1));
        $result = null;
        try {
            $this->initSoapClient();
            $result = $this->client->runTransaction($request);
            $this->logger->info("BT refund response: ".print_r($result, 1));
        } catch (\Exception $e) {
            $this->logger->error("check bank transfer status: " . $e->getMessage());
        }
        return $result;
    }

    /**
     * @param $merchantId
     * @param $reservedOrderId
     * @param array $shippingAddress
     * @param Address|null $billingAddress
     * @return string
     */
    public function checkAddress($merchantId, $reservedOrderId, array $shippingAddress, Address $billingAddress = null)
    {
        
        $request = new \stdClass();
        $request->merchantID = $merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $reservedOrderId;
        
        if (!empty($billingAddress)) {
            $billTo = new \stdClass();
            $billTo->country = $billingAddress->getCountry();
            if (in_array($billTo->country, ['CA', 'US'])) {
                $billTo->state = $billingAddress->getRegionCode();
            }
            $billTo->postalCode = $billingAddress->getPostcode();
            $billTo->street1 = $billingAddress->getData('street1');
            $billTo->city = $billingAddress->getCity();
            $request->billTo = $billTo;
        }
        
        if (!empty($shippingAddress)) {
            $shipTo = new \stdClass();
            $shipTo->country = $shippingAddress['country'];
            if (in_array($shipTo->country, ['CA', 'US'])) {
                $shipTo->state = $shippingAddress['region_code'];
            }
            $shipTo->postalCode = $shippingAddress['postcode'];
            $shipTo->firstName = $shippingAddress['firstname'];
            $shipTo->lastName = $shippingAddress['lastname'];
            $shipTo->street1 = $shippingAddress['street1'];
            $shipTo->city = $shippingAddress['city'];
            $shipTo->phoneNumber = $shippingAddress['telephone'];
            $request->shipTo = $shipTo;
        }
        
        $davService = new \stdClass();
        $davService->run = 'true';
        $request->davService = $davService;
        $this->logger->info(print_r($request, 1));
        $result = '';
        try {
            $result = $this->client->runTransaction($request);
            $this->logger->info(print_r($result, 1));
        } catch (\Exception $e) {
            $this->logger->error("error in address verification service: " . $e->getMessage());
        }
        $this->logger->info("REQUEST:\n" . $this->client->__getLastRequest());
        return $result;
    }
}
