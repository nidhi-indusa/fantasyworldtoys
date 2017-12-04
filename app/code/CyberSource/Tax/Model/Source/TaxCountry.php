<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Tax\Model\Source;

/**
 * Class Country
 * @package CyberSource\Core\Model\Source
 * @codeCoverageIgnore
 */
class TaxCountry extends \Magento\Directory\Model\Config\Source\Country
{
    /**
     * Return options array
     *
     * @param boolean $isMultiselect
     * @param string|array $foregroundCountries
     * @return array
     */
    public function toOptionArray($isMultiselect = false, $foregroundCountries = '')
    {
        if (!$this->_options) {
            $this->_options = $this->_countryCollection->loadData()->setForegroundCountries(
                $foregroundCountries
            )->toOptionArray(
                false
            );
        }

        $options = $this->_options;
        foreach ($options as $i => $option) {
            if (!in_array($option['value'], ['US', 'CA'])) {
                unset($options[$i]);
            }
        }
        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('--Please Select--')]);
        }

        return $options;
    }
}
