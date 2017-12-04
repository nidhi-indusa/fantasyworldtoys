<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Token
 * @package CyberSource\Core\Model\ResourceModel
 * @codeCoverageIgnore
 */
class Token extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('cybersource_payment_token', 'token_id');
    }
}
