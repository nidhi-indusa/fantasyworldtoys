<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Model\ResourceModel;

/**
 * Class Token
 * @package CyberSource\SecureAcceptance\Model\ResourceModel
 * @codeCoverageIgnore
 */
class Token extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('cybersource_payment_token', 'token_id');
    }
}
