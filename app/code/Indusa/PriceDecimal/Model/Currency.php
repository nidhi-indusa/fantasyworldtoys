<?php
/**
 *
 * @package package Indusa\PriceDecimal
 *
 * @author  Indusa Codreanu <Indusa.codreanu@gmail.com>
 */

namespace Indusa\PriceDecimal\Model;


use Magento\Framework\CurrencyInterface;
use Magento\Framework\Currency as MagentoCurrency;


class Currency extends MagentoCurrency implements CurrencyInterface
{

    use PricePrecisionConfigTrait;

    /**
     * @var \Indusa\PriceDecimal\Model\ConfigInterface
     */
    protected $moduleConfig;

    /**
     * Currency constructor.
     *
     * @param \Magento\Framework\App\CacheInterface      $appCache
     * @param null                                       $options
     * @param null                                       $locale
     * @param \Indusa\PriceDecimal\Model\ConfigInterface $moduleConfig
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $appCache, $options = null,
        $locale = null,
        ConfigInterface $moduleConfig
    ) {
        $this->moduleConfig = $moduleConfig;

        $this->_options['precision'] = $this->getPricePrecision();

        parent::__construct($appCache, $options, $locale);
    }


}