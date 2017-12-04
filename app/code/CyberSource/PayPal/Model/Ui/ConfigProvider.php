<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\PayPal\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CyberSource\PayPal\Model\Config;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Class ConfigProvider
 * @codeCoverageIgnore
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const PAYPAL_CODE = 'cybersourcepaypal';

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     *
     * @param Config $config
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        ResolverInterface $localeResolver
    ) {
        $this->config = $config;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $isPayPalActive = $this->config->isActive();
        return [
            'payment' => [
                self::PAYPAL_CODE => [
                    'isActive' => $isPayPalActive,
                    'title' => $this->config->getTitle(),
                    'merchantName' => $this->config->getMerchantName(),
                    'locale' => strtolower($this->localeResolver->getLocale()),
                    'paymentAcceptanceMarkSrc' =>
                        'https://www.paypalobjects.com/webstatic/en_US/i/buttons/pp-acceptance-medium.png',
                    'merchantId' => $this->config->getPayPalMerchantId(),
                    'environment' => $this->config->getEnvironment(),
                    'redirectType' => $this->config->getPayPalRedirectType()
                ]
            ]
        ];
    }
}
