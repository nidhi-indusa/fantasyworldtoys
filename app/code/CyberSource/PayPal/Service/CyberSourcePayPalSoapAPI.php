<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\PayPal\Service;

use CyberSource\PayPal\Helper\RequestDataBuilder;
use CyberSource\Core\Service\AbstractConnection;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class CyberSourcePayPalSoapAPI extends AbstractConnection
{
    const SUCCESS_REASON_CODE = 100;

    /** @var  \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface */
    private $transactionBuilder;

    /**
     * @var \SoapClient
     */
    public $client;

    /**
     * @var int
     */
    private $merchantReferenceCode;

    /**
     * @var RequestDataBuilder
     */
    private $requestDataHelper;

    /** @var Payment $payment */
    private $payment = null;

    /** @var bool $isSuccessfullyVoid */
    public $isSuccessfullyVoid = false;

    /** @var bool $isSuccessfullyReverse */
    public $isSuccessfullyReverse = false;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    private $countryFactory;

    /**
     * Map for billing address import/export
     *
     * @var array
     */
    protected $_billingAddressMap = [
        'payer' => 'email',
        'payerFirstname' => 'firstname',
        'payerLastname' => 'lastname',
        'shipToCountry' => 'country_id', // iso-3166 two-character code
        'shipToState' => 'region',
        'shipToCity' => 'city',
        'shipToAddress1' => 'street',
        'shipToZip' => 'postcode'
    ];

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param RequestDataBuilder $requestDataHelper
     * @param DataObjectFactory $addressFactory
     * @paran \Magento\Directory\Model\CountryFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        BuilderInterface $transactionBuilder,
        RequestDataBuilder $requestDataHelper,
        DataObjectFactory $dataObjectFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory
    ) {
        parent::__construct($scopeConfig, $logger);
        $this->client = $this->getSoapClient();
        $this->transactionBuilder = $transactionBuilder;
        $this->requestDataHelper = $requestDataHelper;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->countryFactory = $countryFactory;
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;
        $this->merchantReferenceCode = $payment->getOrder()->getQuoteId();
    }

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
     * Retrieve PayPal Token for Express Checkout by calling method setService
     *
     * @param \stdClass $request
     * @return array
     * @throws \Exception
     */
    public function setService($request)
    {
        $this->logger->info('SOAP '.__METHOD__);
        $result = null;
        $errorMessage = null;
        try {
            $response = $this->client->runTransaction($request);

            if (null !== $response && isset($response->decision) && $response->decision == 'DECLINE') {
                throw new LocalizedException(__('Sorry but your transaction was unsuccessful.'));
            }

            if ($response === null || 'ERROR' === $response->decision || 100 != $response->reasonCode) {
                $message = ($response !== null && $response->message) ? $response->message : "Unable to process request, check module configuration.";
                throw new LocalizedException(__($message));
            }

            if ($response !== null && 100 == $response->reasonCode) {
                $result = [
                    'paypalToken' => $response->payPalEcSetReply->paypalToken,
                    'requestID' => $response->requestID,
                    'requestToken' => $response->requestToken,
                    'merchantReferenceCode' => $response->merchantReferenceCode
                ];
            }

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $result;
    }

    /**
     * Get PayPal order details
     *
     * @param \stdClass $request
     * @return array
     * @throws \Exception
     */
    public function getDetailsService($request)
    {
        $this->logger->info('SOAP '.__METHOD__);
        $response = null;
        try {
            $result = $this->client->runTransaction($request);

            if (null !== $result && 100 == $result->reasonCode) {
                $shippingAddress = $this->convertPayPalAddressToAddress($result->payPalEcGetDetailsReply);

                $response = [
                    'paypalToken' => $result->payPalEcGetDetailsReply->paypalToken,
                    'paypalPayerId' => $result->payPalEcGetDetailsReply->payerId,
                    'paypalEcSetRequestID' => $result->requestID,
                    'paypalEcSetRequestToken' => $result->requestToken,
                    'paypalCustomerEmail' => $result->payPalEcGetDetailsReply->payer,
                    'shippingAddress' => $shippingAddress,
                    'merchantReferenceCode' => $request->merchantReferenceCode
                ];

            } else {
                throw new LocalizedException(__("Unable to retrieve details from PayPal"));
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $response;
    }

    private function convertPayPalAddressToAddress($data)
    {
        $address = $this->dataObjectFactory->create();
        \Magento\Framework\DataObject\Mapper::accumulateByMap((array) $data, $address, $this->_billingAddressMap);
        $address->setExportedKeys(array_values($this->_billingAddressMap));

        // attempt to fetch region_id from directory
        if ($address->getCountryId() && $address->getRegion()) {
            $regions = $this->countryFactory->create()->loadByCode(
                $address->getCountryId()
            )->getRegionCollection()->addRegionCodeOrNameFilter(
                $address->getRegion()
            )->setPageSize(
                1
            );
            foreach ($regions as $region) {
                $address->setRegionId($region->getId());
                $address->setExportedKeys(array_merge($address->getExportedKeys(), ['region_id']));
                break;
            }
        }

        return $address;
    }

    /**
     * Perform PayPal Payment
     *
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function doPaymentService($request)
    {
        $this->logger->info('SOAP '.__METHOD__);
        $result = null;
        try {
            $result = $this->client->runTransaction($request);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $result;
    }

    /**
     * Perform PayPal Payment
     *
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function orderSetupService($request)
    {
        $this->logger->info('SOAP '.__METHOD__);
        $response = null;
        try {
            $result = $this->client->runTransaction($request);

            if (null !== $result && 100 == $result->reasonCode) {
                $response = $result;
            } else {
                throw new LocalizedException(__("Unable to setup order on PayPal"));
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
        return $response;
    }

    /**
     * Perform PayPal Payment
     *
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function authorizationService($request)
    {
        $this->logger->info('SOAP '.__METHOD__);
        $response = null;
        try {
            $this->logger->info('Paypal auth request = '.print_r($request, 1));
            $result = $this->client->runTransaction($request);

            if (null !== $result && (100 == $result->reasonCode || 480 == $result->reasonCode)) {
                $response = $result;
            } else {
                throw new LocalizedException(__("Unable to Authorize order on PayPal"));
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
        return $response;
    }

    /**
     * Perform PayPal Payment
     *
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function captureService($request)
    {
        $this->logger->info('SOAP '.__METHOD__);
        $response = null;
        try {
            $this->logger->info('Paypal capture request = '.print_r($request, 1));
            $result = $this->client->runTransaction($request);

            if (null !== $result && 100 == $result->reasonCode) {
                $response = $result;
            } else {
                throw new LocalizedException(__("Unable to Capture order on PayPal"));
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $response;
    }

    public function refundService($request)
    {
        $this->logger->info('SOAP '.__METHOD__);
        $result = null;
        try {
            $result = $this->client->runTransaction($request);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $result;
    }

    public function authorizeReversalService($request)
    {
        $this->logger->info('SOAP '.__METHOD__);
        $result = null;
        try {
            $result = $this->client->runTransaction($request);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $result;
    }

    /**
     * Build transaction object
     *
     * @param \stdClass $result
     * @param $type
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function buildTransaction(\stdClass $result, $type)
    {
        $trans = $this->transactionBuilder;

        $resultData = [
            "merchantReferenceCode" => $result->merchantReferenceCode,
            "requestID" => $result->requestID,
            "decision" => $result->decision,
            "reasonCode" => $result->reasonCode,
            "payPalEcSetReply" => (array) $result->payPalEcSetReply
        ];

        $transaction = $trans->setPayment($this->payment)
            ->setOrder($this->payment->getOrder())
            ->setTransactionId($result->requestID)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => $resultData]
            )
            ->setFailSafe(true)
            ->build($type);

        return $transaction;
    }
}
