<?php
/**
 *
 * @package Indusa\PriceDecimal\Model\Plugin
 *
 * @author  Indusa Codreanu <Indusa.codreanu@gmail.com>
 */

namespace Indusa\PriceDecimal\Model\Plugin;

class PriceCurrency extends PriceFormatPluginAbstract
{

    /**
     * {@inheritdoc}
     */
    public function aroundFormat(
        \Magento\Directory\Model\PriceCurrency $subject,
        callable $proceed,
        ...$args
    ) {

        if ($this->getConfig()->isEnable()) {

            if(!isset($args[1])){
                $args[1] = true;
            }

            $args[2] = $this->getPricePrecision(); // Precision argument
        }

        return $proceed(...$args);
    }
}
