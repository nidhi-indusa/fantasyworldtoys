<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model;

class IdealOption extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('CyberSource\BankTransfer\Model\ResourceModel\IdealOption');
    }
}
