<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model;

/**
 * Class Token
 * @package CyberSource\Core\Model
 * @codeCoverageIgnore
 */
class Token extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('CyberSource\Core\Model\ResourceModel\Token');
    }
}
