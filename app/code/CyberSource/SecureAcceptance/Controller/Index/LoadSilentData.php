<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

use Magento\Framework\App\Action\Context;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;

class LoadSilentData extends \Magento\Framework\App\Action\Action
{
    /**
     * @var RequestDataBuilder
     */
    protected $_helper;

    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    
    /**
     * LoadSilentData constructor.
     * @param Context $context
     * @param RequestDataBuilder $helper
     */
    public function __construct(
        Context $context,
        RequestDataBuilder $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->_helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $guestEmail = trim($this->_request->getParam('quoteEmail'));
        $isTokenPay = $this->_request->getParam('isTokenPay');
        $token = $this->_request->getParam('token');
        if ($this->scopeConfig->getValue("payment/chcybersource/test_mode", \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $config_service_url = 'sop_service_url_test';
        } else {
            $config_service_url = 'sop_service_url';
        }
        if (empty($guestEmail) || $guestEmail == 'null') {
            $guestEmail = null;
        }
        $data = [
            'form_data' => $this->_helper->buildSilentRequestData($guestEmail, $isTokenPay, $token),
            'action_url' => $this->scopeConfig->getValue("payment/chcybersource/".$config_service_url, \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'/silent/pay'
        ];
        $result = $this->resultJsonFactory->create();
        $result = $result->setData($data);
        return $result;
    }
}
