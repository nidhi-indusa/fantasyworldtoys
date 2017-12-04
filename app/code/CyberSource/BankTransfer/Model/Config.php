<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model;

use CyberSource\Core\Model\AbstractGatewayConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 */
class Config extends AbstractGatewayConfig
{

    const KEY_TITLE = 'bank_transfer_title';
    
    const KEY_ACTIVE = 'bank_transfer_active';
    
    protected $method;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode,
        $pathPattern
    ) {
        parent::__construct($scopeConfig, 'cybersource_bank_transfer', $pathPattern);
    }

    public function isActive()
    {
        return $this->getValue(self::KEY_ACTIVE);
    }
    
    public function isMethodActive($method)
    {
        return $this->getValue($method.'_active');
    }

    public function getMethodTitle($method)
    {
        return $this->getValue($method.'_title');
    }

    public function getMethodAvailableCurrencies($method)
    {
        return explode(',', $this->getValue($method.'_currency'));
    }
    
    public function getCode()
    {
        return Payment::CODE;
    }
}
