<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CyberSource\PayPal\Controller\Express;

class UpdateShippingMethods extends \CyberSource\PayPal\Controller\Express\AbstractExpress\UpdateShippingMethods
{
    /**
     * Config mode type
     *
     * @var string
     */
    protected $_configType = \CyberSource\PayPal\Model\Config::class;

    /**
     * Config method type
     *
     * @var string
     */
    protected $_configMethod = \CyberSource\PayPal\Model\Config::CODE;

    /**
     * Checkout mode type
     *
     * @var string
     */
    protected $_checkoutType = \CyberSource\PayPal\Model\Express\Checkout::class;
}
