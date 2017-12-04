<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class IdealOption
 * @package CyberSource\BankTransfer\Model\ResourceModel
 * @codeCoverageIgnore
 */
class IdealOption extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('cybersource_ideal_option', 'id');
    }
}
