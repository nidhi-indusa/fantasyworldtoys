<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Service;

use Psr\Log\LoggerInterface;

abstract class AbstractConnection
{
    const XML_NAMESPACE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    const IS_TEST_MODE_CONFIG_PATH = 'payment/chcybersource/use_test_wsdl';
    const TEST_WSDL_PATH           = 'payment/chcybersource/path_to_test_wsdl';
    const LIVE_WSDL_PATH           = 'payment/chcybersource/path_to_wsdl';
    const MERCHANT_ID_PATH         = 'payment/chcybersource/merchant_id';
    const TRANSACTION_KEY_PATH     = 'payment/chcybersource/transaction_key';

    /**
     * @var string
     */
    private $wsdl = null;

    /**
     * @var string
     */
    private $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    /**
     * @var string
     */
    public $merchantId = null;

    /**
     * @var string
     */
    public $transactionKey = null;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $config;

    /** @var LoggerInterface $logger */
    protected $logger;

    /**
     * @var \SoapClient $client
     */
    public $client;

    /**
     * AbstractConnection constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->config = $scopeConfig;
        $this->logger = $logger;

        $this->handleWsdlEnvironment();
        $this->setUpCredentials();
        $this->initSoapClient();
    }

    /**
     * Initialize SOAP Client
     *
     * @return \SoapClient
     * @throws \SoapFault
     */
    public function initSoapClient()
    {
        $opts = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ],
            'http'=> [
                'user_agent' => 'PHPSoapClient'
            ]
        ];

        $params = [
            'encoding' => 'UTF-8',
            'verifypeer' => false,
            'verifyhost' => false,
            'soap_version' => SOAP_1_1,
            'trace' => 1,
            'exceptions' => 1,
            "connection_timeout" => 180,
            'stream_context' => stream_context_create($opts)
        ];

        try {
            if ($this->wsdl !== null) {
//                $client = new \Zend_Soap_Client($this->wsdl);
                $client = new \SoapClient($this->wsdl, $params);

                $nameSpace = self::XML_NAMESPACE;

                $soapUsername = new \SoapVar(
                    $this->merchantId,
                    XSD_STRING,
                    null,
                    $nameSpace,
                    null,
                    $nameSpace
                );

                $soapPassword = new \SoapVar(
                    $this->transactionKey,
                    XSD_STRING,
                    null,
                    $nameSpace,
                    null,
                    $nameSpace
                );

                $auth = new \stdClass();
                $auth->Username = $soapUsername;
                $auth->Password = $soapPassword;

                $soapAuth = new \SoapVar(
                    $auth,
                    SOAP_ENC_OBJECT,
                    null,
                    $nameSpace,
                    'UsernameToken',
                    $nameSpace
                );

                $token = new \stdClass();
                $token->UsernameToken = $soapAuth;

                $soapToken = new \SoapVar(
                    $token,
                    SOAP_ENC_OBJECT,
                    null,
                    $nameSpace,
                    'UsernameToken',
                    $nameSpace
                );

                $security = new \SoapVar(
                    $soapToken,
                    SOAP_ENC_OBJECT,
                    null,
                    $nameSpace,
                    'Security',
                    $nameSpace
                );

                $header = new \SoapHeader($nameSpace, 'Security', $security, true);
//                $client->addSoapInputHeader($header);
                $client->__setSoapHeaders([$header]);
                $this->client = $client;
                return $this->client;
            }
        } catch (\SoapFault $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return null;
    }

    public function setSoapClient(\SoapClient $client)
    {
        $this->client = $client;
    }

    public function getSoapClient()
    {
        return $this->client;
    }

    /**
     * Handle WSDL Environment to use correct webservice based on environment config
     */
    private function handleWsdlEnvironment()
    {
        $isTestMode = $this->config->getValue(
            self::IS_TEST_MODE_CONFIG_PATH,
            $this->storeScope
        );

        if ($isTestMode) {
            $this->wsdl = $this->config->getValue(
                self::TEST_WSDL_PATH,
                $this->storeScope
            );
        } else {
            $this->wsdl = $this->config->getValue(
                self::LIVE_WSDL_PATH,
                $this->storeScope
            );
        }
    }

    /**
     * Setup Credentials for webservice
     */
    private function setUpCredentials()
    {
        $this->merchantId = $this->config->getValue(
            self::MERCHANT_ID_PATH,
            $this->storeScope
        );
        $this->transactionKey = $this->config->getValue(
            self::TRANSACTION_KEY_PATH,
            $this->storeScope
        );
    }
    
    /**
     * Setup Credentials for webservice
     *
     * @param string $bankTransferPaymentMethod
     */
    public function setBankTransferCredentials($bankTransferPaymentMethod = 'ideal')
    {
        $this->merchantId = $this->config->getValue('payment/cybersource_bank_transfer/'.$bankTransferPaymentMethod.'_merchant_id', $this->storeScope);
        $this->transactionKey = $this->config->getValue('payment/cybersource_bank_transfer/'.$bankTransferPaymentMethod.'_transaction_key', $this->storeScope);
    }
    
    public function setCredentialsByStore($storeId)
    {
        $this->merchantId = $this->config->getValue(
            self::MERCHANT_ID_PATH,
            'store',
            $storeId
        );
        $this->transactionKey = $this->config->getValue(
            self::TRANSACTION_KEY_PATH,
            'store',
            $storeId
        );
    }
}
