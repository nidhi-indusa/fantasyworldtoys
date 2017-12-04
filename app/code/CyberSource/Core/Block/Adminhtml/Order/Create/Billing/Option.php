<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Block\Adminhtml\Order\Create\Billing;

use CyberSource\Core\Helper\Data;

/**
 * Show tokens for admin order
 */
class Option extends \Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form
{

    /** @var Data */
    private $helper;

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        Data $helper,
        array $data
    ) {
        parent::__construct($context, $paymentHelper, $methodSpecificationFactory, $sessionQuote, $data);
        $this->helper = $helper;
    }
}
