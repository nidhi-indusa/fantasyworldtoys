<?php
/**
 *
 * @package package Indusa\PriceDecimal\Model\Plugin\Local
 *
 * @author  Indusa Codreanu <Indusa.codreanu@gmail.com>
 */

namespace Indusa\PriceDecimal\Model\Plugin\Local;

use Indusa\PriceDecimal\Model\Plugin\PriceFormatPluginAbstract;

class Format extends PriceFormatPluginAbstract
{

    /**
     * {@inheritdoc}
     *
     * @param $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterGetPriceFormat($subject, $result)
    {
        $precision = $this->getPricePrecision();

        if ($this->getConfig()->isEnable()) {
            $result['precision'] = $precision;
            $result['requiredPrecision'] = $precision;
        }

        return $result;
    }
}
