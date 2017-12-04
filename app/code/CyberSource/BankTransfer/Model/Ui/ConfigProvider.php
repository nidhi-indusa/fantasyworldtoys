<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CyberSource\BankTransfer\Model\Config;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const BANK_TRANSFER_CODE = 'cybersource_bank_transfer';

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Config $config
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        ResolverInterface $localeResolver,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->logger = $logger;
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
                'cybersource_bank_transfer_ideal' => [
                    'active' => $this->config->isMethodActive('ideal'),
                    'title' => $this->config->getMethodTitle('ideal'),
                ],
                'cybersource_bank_transfer_sofort' => [
                    'active' => $this->config->isMethodActive('sofort'),
                    'title' => $this->config->getMethodTitle('sofort'),
                ],
                'cybersource_bank_transfer_bancontact' => [
                    'active' => $this->config->isMethodActive('bancontact'),
                    'title' => $this->config->getMethodTitle('bancontact'),
                ],
            ]
        ];
    }
}
