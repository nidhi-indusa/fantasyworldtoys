<?php
/**
 *
 * @package Indusa\PriceDecimal\Model\Plugin
 *
 * @author  Indusa Codreanu <Indusa.codreanu@gmail.com>
 */


namespace Indusa\PriceDecimal\Model\Plugin;

use Indusa\PriceDecimal\Model\ConfigInterface;
use Indusa\PriceDecimal\Model\PricePrecisionConfigTrait;

abstract class PriceFormatPluginAbstract
{

    use PricePrecisionConfigTrait;

    /** @var ConfigInterface  */
    protected $moduleConfig;

    /**
     * @param \Indusa\PriceDecimal\Model\ConfigInterface $moduleConfig
     */
    public function __construct(
        ConfigInterface $moduleConfig
    ) {
        $this->moduleConfig  = $moduleConfig;
    }
}
