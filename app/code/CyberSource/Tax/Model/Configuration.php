<?php

namespace CyberSource\Tax\Model;

class Configuration extends \Magento\Tax\Model\Config
{
    const TAX_ENABLED = 'tax/cybersourcetax/tax_enabled';
    const TAX_SHIP_FROM_CITY = 'tax/cybersourcetax/ship_from_city';
    const TAX_SHIP_FROM_POSTCODE = 'tax/cybersourcetax/ship_from_postcode';
    const TAX_SHIP_FROM_COUNTRY = 'tax/cybersourcetax/ship_from_country';
    const TAX_SHIP_FROM_REGION = 'tax/cybersourcetax/ship_from_region';
    const TAX_ACCEPTANCE_CITY = 'tax/cybersourcetax/acceptance_city';
    const TAX_ACCEPTANCE_POSTCODE = 'tax/cybersourcetax/acceptance_postcode';
    const TAX_ACCEPTANCE_COUNTRY = 'tax/cybersourcetax/acceptance_country';
    const TAX_ACCEPTANCE_REGION = 'tax/cybersourcetax/acceptance_region';
    const TAX_ORIGIN_CITY = 'tax/cybersourcetax/origin_city';
    const TAX_ORIGIN_POSTCODE = 'tax/cybersourcetax/origin_postcode';
    const TAX_ORIGIN_COUNTRY = 'tax/cybersourcetax/origin_country';
    const TAX_ORIGIN_REGION = 'tax/cybersourcetax/origin_region';
    const TAX_MERCHANT_VAT = 'tax/cybersourcetax/merchant_vat';
    const TAX_NEXUS_REGION = 'tax/cybersourcetax/cybersource_nexus_regions';
    const TAX_DEFAULT_CODE = 'cybersourcedefaulttax';

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * Configuration constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($scopeConfig);
    }

    public function getTaxShipFromCity()
    {
        return $this->_scopeConfig->getValue(self::TAX_SHIP_FROM_CITY,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxShipFromCountry()
    {
        return $this->_scopeConfig->getValue(self::TAX_SHIP_FROM_COUNTRY,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxShipFromRegion()
    {
        return $this->_scopeConfig->getValue(self::TAX_SHIP_FROM_REGION,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxShipFromPostcode()
    {
        return $this->_scopeConfig->getValue(self::TAX_SHIP_FROM_POSTCODE,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxNexusRegions($separator = null)
    {
        if ($separator) {
            return str_replace(',', $separator, $this->_scopeConfig->getValue(self::TAX_NEXUS_REGION,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        }

        return $this->_scopeConfig->getValue(self::TAX_NEXUS_REGION,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxAcceptanceCity()
    {
        return $this->_scopeConfig->getValue(self::TAX_ACCEPTANCE_CITY,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxAcceptanceCountry()
    {
        return $this->_scopeConfig->getValue(self::TAX_ACCEPTANCE_COUNTRY,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxAcceptanceRegion()
    {
        return $this->_scopeConfig->getValue(self::TAX_ACCEPTANCE_REGION,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxAcceptancePostcode()
    {
        return $this->_scopeConfig->getValue(self::TAX_ACCEPTANCE_POSTCODE,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxOriginCity()
    {
        return $this->_scopeConfig->getValue(self::TAX_ORIGIN_CITY,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxOriginCountry()
    {
        return $this->_scopeConfig->getValue(self::TAX_ORIGIN_COUNTRY,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxOriginRegion()
    {
        return $this->_scopeConfig->getValue(self::TAX_ORIGIN_REGION,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxOriginPostcode()
    {
        return $this->_scopeConfig->getValue(self::TAX_ORIGIN_POSTCODE,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTaxMerchantVat()
    {
        return $this->_scopeConfig->getValue(self::TAX_MERCHANT_VAT,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
