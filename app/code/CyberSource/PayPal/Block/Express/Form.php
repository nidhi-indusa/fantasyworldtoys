<?php

namespace CyberSource\PayPal\Block\Express;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Paypal\Helper\Data;
use CyberSource\PayPal\Model\Config;
use Magento\Paypal\Model\ConfigFactory;

class Form extends \Magento\Paypal\Block\Express\Form
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_methodCode = Config::CODE;

    /**
     * @param Context $context
     * @param ConfigFactory $paypalConfigFactory
     * @param ResolverInterface $localeResolver
     * @param Data $paypalData
     * @param CurrentCustomer $currentCustomer
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigFactory $paypalConfigFactory,
        ResolverInterface $localeResolver,
        Data $paypalData,
        CurrentCustomer $currentCustomer,
        array $data = []
    ) {
        parent::__construct($context, $paypalConfigFactory, $localeResolver, $paypalData, $currentCustomer, $data);
    }
}
