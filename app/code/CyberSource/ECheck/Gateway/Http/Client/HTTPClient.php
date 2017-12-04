<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Http\Client;

use CyberSource\ECheck\Gateway\Config\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class HTTPClient implements ClientInterface
{
    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $_httpClientFactory;

    /**
     * @var ZendClient
     */
    protected $_client;

    /**
     * @var  Config
     */
    protected $_config;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    
    public function __construct(
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        Config $config,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_httpClientFactory = $httpClientFactory;
        $this->_config = $config;
        $this->logger = $logger;
        $this->createClient();
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @throws LocalizedException
     * @return string
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();
        $this->logger->info('client echeck request = '.print_r($request, 1));
        $this->_client->setParameterPost($request);
        try {
            $response = $this->_client->request(\Zend_Http_Client::POST)->getBody();
            $this->logger->info('echeck response = '.$response);
            return (array) (object) simplexml_load_string($response);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to retrieve payment information'));
        }
    }

    private function createClient()
    {
        /** @var ZendClient $client */
        $client = $this->_httpClientFactory->create();
        $client->setUri($this->_config->getServerUrl());
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setAuth($this->_config->getMerchantUsername(), $this->_config->getMerchantPassword());

        $this->_client = $client;
    }
}
