<?php
/**
 *
 * @package Indusa\PriceDecimal
 *
 * @author  Indusa Codreanu <Indusa.codreanu@gmail.com>
 */

namespace Indusa\PriceDecimal\Model;


trait PricePrecisionConfigTrait
{


    /**
     * @return \Indusa\PriceDecimal\Model\ConfigInterface
     */
    public function getConfig()
    {
        return $this->moduleConfig;
    }

    /**
     * @return int|mixed
     */
    public function getPricePrecision()
    {
        if ($this->getConfig()->canShowPriceDecimal()) {
            return $this->getConfig()->getPricePrecision();
        }

        return 0;
    }

}