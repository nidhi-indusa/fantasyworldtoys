<?php
/**
 *
 * @package Indusa\PriceDecimal\Model
 *
 * @author  Indusa Codreanu <Indusa.codreanu@gmail.com>
 */


namespace Indusa\PriceDecimal\Model;

interface ConfigInterface
{
    /**
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public function getScopeConfig();

    /**
     * @return mixed
     */
    public function isEnable();
}
