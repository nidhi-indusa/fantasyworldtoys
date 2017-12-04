<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\Core\Model;

use CyberSource\Core\Model\AbstractGatewayConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 * @package CyberSource\Core\Model
 * @codeCoverageIgnore
 */
class Config extends AbstractGatewayConfig
{
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode,
        $pathPattern
    ) {
        parent::__construct($scopeConfig, 'chcybersource', $pathPattern);
    }

    public function getTitle()
    {
        return $this->getValue('title');
    }
    
    public function isActive()
    {
        return $this->getValue('active');
    }
    
    public function isSilent()
    {
        return ($this->getValue('secureacceptance_type') != 'web');
    }
}
