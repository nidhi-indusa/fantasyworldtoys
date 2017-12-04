<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Model;

/**
 * Class Token
 * @package CyberSource\SecureAcceptance\Model
 * @codeCoverageIgnore
 */
class Token extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('CyberSource\SecureAcceptance\Model\ResourceModel\Token');
    }
}
