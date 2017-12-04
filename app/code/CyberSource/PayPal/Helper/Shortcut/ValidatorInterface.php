<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace CyberSource\PayPal\Helper\Shortcut;

/**
 * Interface \CyberSource\PayPal\Helper\Shortcut\ValidatorInterface
 *
 */
interface ValidatorInterface
{
    /**
     * Validates shortcut
     *
     * @param string $code
     * @param bool $isInCatalog
     * @return bool
     */
    public function validate($code, $isInCatalog);
}
