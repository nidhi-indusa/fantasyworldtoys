<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

use Magento\Framework\App\Action\Context;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;

class SilentFormIframe extends \Magento\Framework\App\Action\Action
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
     * LoadSilentData constructor.
     * @param Context $context
     * @param RequestDataBuilder $helper
     */
    public function __construct(
        Context $context,
        RequestDataBuilder $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_helper = $helper;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->scopeConfig->getValue("payment/chcybersource/test_mode", \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $config_service_url = 'sop_service_url_test';
        } else {
            $config_service_url = 'sop_service_url';
        }
        $html = '<html><head></head><body>SILENT FORM<form id="cybersource-iframe-form" action="'.$this->scopeConfig->getValue("payment/chcybersource/".$config_service_url, \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'/silent/embedded/pay" method="post">';
        $html .= '</form></body></html>';
        $result = $this->resultFactory->create('raw');
        $result->setContents($html);
        return $result;
    }
}
