<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Config;

use CyberSource\Core\Model\AbstractGatewayConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 */
class Config extends AbstractGatewayConfig
{
    const KEY_TITLE = 'title';
    const KEY_ACTIVE = 'active';
    const KEY_PAYMENT_ACTION = 'payment_action';
    const KEY_MERCHANT_PASSWORD = 'merchant_password';
    const KEY_MERCHANT_USERNAME = 'merchant_username';
    const KEY_IS_TEST = 'is_test';

    protected $method;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode,
        $pathPattern
    ) {
        $this->method = $methodCode;
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    public function isActive()
    {
        return $this->getValue(self::KEY_ACTIVE);
    }

    public function getTitle()
    {
        return $this->getValue(self::KEY_TITLE);
    }

    public function getPaymentAction()
    {
        return 'authorize';
    }

    public function setMethod($methodCode)
    {
        $this->method = $methodCode;
    }

    public function getConfigPaymentAction()
    {
        return 'authorize';
    }

    public function getMerchantId()
    {
        $this->setMethodCode('chcybersource');
        $merchantId = parent::getMerchantId();
        $this->setMethodCode('cybersourceecheck');
        return $merchantId;
    }

    public function getMerchantPassword()
    {
        return $this->getValue(self::KEY_MERCHANT_PASSWORD);
    }

    public function getMerchantUsername()
    {
        return $this->getValue(self::KEY_MERCHANT_USERNAME);
    }

    public function getTestEventType()
    {
        return $this->getValue('test_event_type');
    }

    public function getAcceptEventType()
    {
        return explode(',', $this->getValue('accept_event_type'));
    }

    public function getRejectEventType()
    {
        return explode(',', $this->getValue('reject_event_type'));
    }

    public function getPendingEventType()
    {
        return explode(',', $this->getValue('pending_event_type'));
    }

    public function isTestMode()
    {
        return ($this->getValue(self::KEY_IS_TEST) === 0) ? false : true;
    }

    public function getServerUrl()
    {
        $url = $this->getValue('service_url');
        if ($this->isTestMode()) {
            $url = $this->getValue('test_service_url');
        }
        return $url;
    }
}
