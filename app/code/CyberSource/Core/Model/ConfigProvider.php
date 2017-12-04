<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use CyberSource\Core\Model\Config;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Class ConfigProvider
 * @codeCoverageIgnore
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'chcybersource';

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
        return [
            'payment' => [
                self::CODE => [
                    'title' => $this->config->getTitle(),
                    'isSilent' => $this->config->isSilent(),
                    'active' => $this->config->isActive()
                ]
            ]
        ];
    }
}
