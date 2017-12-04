<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model\ResourceModel\Token;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package CyberSource\Core\Model\ResourceModel\Token
 * @codeCoverageIgnore
 */
class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'CyberSource\Core\Model\Token',
            'CyberSource\Core\Model\ResourceModel\Token'
        );
    }
}
