<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Tax\Model\Source;

/**
 * Class Allregion
 * @package CyberSource\Core\Model\Source
 * @codeCoverageIgnore
 */
class AllRegion implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected $_countries;

    /**
     * @var array
     */
    protected $_options;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $_countryCollectionFactory;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    protected $_regionCollectionFactory;

    /**
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     */
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
    ) {
        $this->_countryCollectionFactory = $countryCollectionFactory;
        $this->_regionCollectionFactory = $regionCollectionFactory;
    }

    /**
     * @param bool $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = false)
    {
        if (!$this->_options) {
            $countriesArray = $this->_countryCollectionFactory->create()->load()->toOptionArray(false);
            $this->_countries = [];
            foreach ($countriesArray as $a) {
                if (in_array($a['value'], ['CA', 'US'])) {
                    $this->_countries[$a['value']] = $a['label'];
                }
            }

            $countryRegions = [];
            $regionsCollection = $this->_regionCollectionFactory->create()->load();
            foreach ($regionsCollection as $region) {
                if (in_array($region->getCountryId(), ['CA', 'US'])) {
                    $countryRegions[$region->getCountryId()][$region->getCode()] = $region->getDefaultName();
                }
            }
            uksort($countryRegions, [$this, 'sortRegionCountries']);

            $this->_options = [];
            foreach ($countryRegions as $countryId => $regions) {
                if (in_array($countryId, ['CA', 'US'])) {
                    $regionOptions = [];
                    foreach ($regions as $regionId => $regionName) {
                        $regionOptions[] = ['label' => $regionName, 'value' => $regionId];
                    }
                    $this->_options[] = ['label' => $this->_countries[$countryId], 'value' => $regionOptions];
                }
            }
        }
        $options = $this->_options;
        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => '']);
        }

        return $options;
    }

    /**
     * @param string $a
     * @param string $b
     * @return int
     */
    public function sortRegionCountries($a, $b)
    {
        return strcmp($this->_countries[$a], $this->_countries[$b]);
    }
}
