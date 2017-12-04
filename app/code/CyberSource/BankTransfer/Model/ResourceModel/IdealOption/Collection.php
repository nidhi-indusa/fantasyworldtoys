<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model\ResourceModel\IdealOption;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package CyberSource\BankTransfer\Model\ResourceModel\IdealOption
 * @codeCoverageIgnore
 */
class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'CyberSource\BankTransfer\Model\IdealOption',
            'CyberSource\BankTransfer\Model\ResourceModel\IdealOption'
        );
    }
}
