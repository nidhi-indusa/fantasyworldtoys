<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Model;

use CyberSource\Core\Model\AbstractGatewayConfig;

/**
 * Class Config
 */
class Config extends AbstractGatewayConfig
{
    public function getCode()
    {
        return Payment::CODE;
    }
}
