<?php

namespace CyberSource\PayPal\Block\Express\InContext;

use CyberSource\PayPal\Model\Source\RedirectionType;
use Magento\Paypal\Model\Config;
use CyberSource\PayPal\Model\Config as CyberSourcePayPalConfig;
use Magento\Paypal\Model\ConfigFactory;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;

/**
 * Class Component
 *
 * @api
 * @since 100.1.0
 */
class Component extends Template
{
    const IS_BUTTON_CONTEXT_INDEX = 'is_button_context';

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CyberSourcePayPalConfig
     */
    private $gatewayConfig;

    /**
     * @inheritdoc
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Context $context,
        ResolverInterface $localeResolver,
        ConfigFactory $configFactory,
        CyberSourcePayPalConfig $gatewayConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->localeResolver = $localeResolver;
        $this->config = $configFactory->create();
        $this->config->setMethod(CyberSourcePayPalConfig::CODE);
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    protected function _toHtml()
    {
        if (!$this->isInContext()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return bool
     */
    private function isInContext()
    {
        $redirectType = $this->gatewayConfig->getPayPalRedirectType();

        if ($redirectType == RedirectionType::IN_CONTEXT) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getEnvironment()
    {
        return (int) $this->gatewayConfig->isTestMode() ? 'sandbox' : 'production';
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getLocale()
    {
        return $this->localeResolver->getLocale();
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getMerchantId()
    {
        return $this->gatewayConfig->getMerchantId();
    }

    /**
     * @return bool
     * @since 100.1.0
     */
    public function isButtonContext()
    {
        return true;
//        return (bool) $this->getData(self::IS_BUTTON_CONTEXT_INDEX);
    }
}
