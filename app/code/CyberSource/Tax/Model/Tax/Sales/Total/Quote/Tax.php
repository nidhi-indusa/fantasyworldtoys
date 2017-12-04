<?php

namespace CyberSource\Tax\Model\Tax\Sales\Total\Quote;

use CyberSource\Tax\Model\Configuration as TaxConfiguration;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Tax extends \Magento\Tax\Model\Sales\Total\Quote\Tax
{
    /**
     * @var \CyberSource\Tax\Service\CyberSourceSoapAPI
     */
    protected $cyberSourceAPI;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var \CyberSource\Tax\Model\Tax\TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory
     * @param \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $customerAddressFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $customerAddressRegionFactory
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \CyberSource\Tax\Service\CyberSourceSoapAPI $cyberSourceSOAP
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory $extensionFactory
     * @param \CyberSource\Tax\Model\Tax\TaxCalculation $taxCalculation
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService,
        \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $customerAddressFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $customerAddressRegionFactory,
        \Magento\Tax\Helper\Data $taxData,
        \CyberSource\Tax\Service\CyberSourceSoapAPI $cyberSourceSOAP,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory $extensionFactory,
        \CyberSource\Tax\Model\Tax\TaxCalculation $taxCalculation
    ) {
        $this->cyberSourceAPI = $cyberSourceSOAP;
        $this->scopeConfig = $scopeConfig;
        $this->priceCurrency = $priceCurrency;
        $this->extensionFactory = $extensionFactory;
        $this->taxCalculation = $taxCalculation;

        parent::__construct(
            $taxConfig,
            $taxCalculationService,
            $quoteDetailsDataObjectFactory,
            $quoteDetailsItemDataObjectFactory,
            $taxClassKeyDataObjectFactory,
            $customerAddressFactory,
            $customerAddressRegionFactory,
            $taxData
        );
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this|\Magento\Tax\Model\Sales\Total\Quote\Tax
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $this->clearValues($total);
        if (!$shippingAssignment->getItems()) {
            return $this;
        }

        $isEnabled = $this->scopeConfig->getValue(TaxConfiguration::TAX_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($isEnabled) {
            $baseQuoteTaxDetails = $this->getQuoteTaxDetailsInterface($shippingAssignment, $total, true);
            $this->cyberSourceAPI->getTaxForOrder($quote, $baseQuoteTaxDetails, $shippingAssignment);
        }

        if ($this->cyberSourceAPI->isValidResponse()) {
            $quoteTax = $this->getQuoteTax($quote, $shippingAssignment, $total);

            //Populate address and items with tax calculation results
            $itemsByType = $this->organizeItemTaxDetailsByType($quoteTax['tax_details'], $quoteTax['base_tax_details']);

            if (isset($itemsByType[self::ITEM_TYPE_PRODUCT])) {
                $this->processProductItems($shippingAssignment, $itemsByType[self::ITEM_TYPE_PRODUCT], $total);
            }

            if (isset($itemsByType[self::ITEM_TYPE_SHIPPING])) {
                $shippingTaxDetails = $itemsByType[self::ITEM_TYPE_SHIPPING][self::ITEM_CODE_SHIPPING][self::KEY_ITEM];
                $baseShippingTaxDetails =
                    $itemsByType[self::ITEM_TYPE_SHIPPING][self::ITEM_CODE_SHIPPING][self::KEY_BASE_ITEM];
                $this->processShippingTaxInfo(
                    $shippingAssignment,
                    $total,
                    $shippingTaxDetails,
                    $baseShippingTaxDetails
                );
            }

            //Process taxable items that are not product or shipping
            $this->processExtraTaxables($total, $itemsByType);

            //Save applied taxes for each item and the quote in aggregation
            $this->processAppliedTaxes($total, $shippingAssignment, $itemsByType);

            if ($this->includeExtraTax()) {
                $total->addTotalAmount('extra_tax', $total->getExtraTaxAmount());
                $total->addBaseTotalAmount('extra_tax', $total->getBaseExtraTaxAmount());
            }
        } else {
            return parent::collect($quote, $shippingAssignment, $total);
        }

        return $this;
    }

    /**
     * Get quote tax details
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array
     */
    protected function getQuoteTax(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $baseTaxDetailsInterface = $this->getQuoteTaxDetailsInterface($shippingAssignment, $total, true);
        $taxDetailsInterface = $this->getQuoteTaxDetailsInterface($shippingAssignment, $total, false);

        $baseTaxDetails = $this->getQuoteTaxDetailsOverride($quote, $baseTaxDetailsInterface, true);
        $taxDetails = $this->getQuoteTaxDetailsOverride($quote, $taxDetailsInterface, false);

        return [
            'base_tax_details' => $baseTaxDetails,
            'tax_details' => $taxDetails
        ];
    }

    /**
     * Get tax details interface based on the quote and items
     *
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @param bool $useBaseCurrency
     * @return \Magento\Tax\Api\Data\QuoteDetailsInterface
     */
    protected function getQuoteTaxDetailsInterface($shippingAssignment, $total, $useBaseCurrency)
    {
        $address = $shippingAssignment->getShipping()->getAddress();
        //Setup taxable items
        $priceIncludesTax = $this->_config->priceIncludesTax($address->getQuote()->getStore());
        $itemDataObjects = $this->mapItems($shippingAssignment, $priceIncludesTax, $useBaseCurrency);

        //process extra taxable items associated only with quote
        $quoteExtraTaxables = $this->mapQuoteExtraTaxables(
            $this->quoteDetailsItemDataObjectFactory,
            $address,
            $useBaseCurrency
        );
        if (!empty($quoteExtraTaxables)) {
            $itemDataObjects = array_merge($itemDataObjects, $quoteExtraTaxables);
        }

        //Preparation for calling taxCalculationService
        $quoteDetails = $this->prepareQuoteDetails($shippingAssignment, $itemDataObjects);

        return $quoteDetails;
    }

    /**
     * Get quote tax details for calculation
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $taxDetails
     * @param bool $useBaseCurrency
     * @return array
     */
    public function getQuoteTaxDetailsOverride(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Tax\Api\Data\QuoteDetailsInterface $taxDetails,
        $useBaseCurrency
    ) {
        $store = $quote->getStore();
        $taxDetails = $this->taxCalculation->calculateTaxDetails($taxDetails, $useBaseCurrency, $store);
        return $taxDetails;
    }

    /**
     * Map an item to item data object
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $itemDataObjectFactory
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param bool $priceIncludesTax
     * @param bool $useBaseCurrency
     * @param string $parentCode
     * @return \Magento\Tax\Api\Data\QuoteDetailsItemInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function mapItem(
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $priceIncludesTax,
        $useBaseCurrency,
        $parentCode = null
    ) {
        $itemDataObject = parent::mapItem(
            $itemDataObjectFactory,
            $item,
            $priceIncludesTax,
            $useBaseCurrency,
            $parentCode
        );

        $lineItemTax = $this->cyberSourceAPI->getItemFromResponse($itemDataObject);

        /**
         * @var \Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterface $extensionAttributes
         */
        $extensionAttributes = $itemDataObject->getExtensionAttributes()
            ? $itemDataObject->getExtensionAttributes()
            : $this->extensionFactory->create();

        $extensionAttributes->setTaxCollectable($lineItemTax['totalTaxAmount']);
        $taxPercent = 0;
        if (($item->getPrice() - $itemDataObject->getDiscountAmount()) != 0 &&
            !is_null($lineItemTax) &&
            array_key_exists('taxableAmount', $lineItemTax)
        ){
            $taxPercent = round(100 * $lineItemTax['totalTaxAmount'] / $lineItemTax['taxableAmount'], 4);
        }

        $extensionAttributes->setCombinedTaxRate($taxPercent);
        $extensionAttributes->setProductType($item->getProductType());
        $extensionAttributes->setPriceType($item->getProduct()->getPriceType());
        $extensionAttributes->setData('sku', $item->getProduct()->getSku());
        $extensionAttributes->setData('product_name', $item->getProduct()->getName());
        $extensionAttributes->setData('product_id', $item->getProduct()->getId());

        $jurisdictionRates = [];
        if (!is_null($lineItemTax) && array_key_exists('jurisdiction', $lineItemTax)) {
            $jurisdictions = (is_array($lineItemTax['jurisdiction'])) ? $lineItemTax['jurisdiction'] : [$lineItemTax['jurisdiction']];

            foreach ($jurisdictions as $jurisdiction) {
                if (empty($jurisdictionRates[$jurisdiction->taxName])) {
                    $jurisdictionRates[$jurisdiction->taxName] = [
                        'rate' => round(100 * $jurisdiction->rate),
                        'amount' => $jurisdiction->taxAmount
                    ];
                }
            }
        }

        $extensionAttributes->setJurisdictionTaxRates($jurisdictionRates);

        $itemDataObject->setExtensionAttributes($extensionAttributes);

        return $itemDataObject;
    }
}
