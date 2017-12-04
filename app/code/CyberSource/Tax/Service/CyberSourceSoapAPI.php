<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Tax\Service;

use CyberSource\Core\Helper\RequestDataBuilder;
use CyberSource\Core\Model\Config;
use CyberSource\Core\Service\AbstractConnection;
use CyberSource\Tax\Model\Configuration;
use Magento\Framework\App\ProductMetadata;
use Magento\Quote\Model\Quote\Address;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class CyberSourceSoapAPI extends AbstractConnection
{
    const SUCCESS_REASON_CODE = 100;

    /**
     * @var \SoapClient
     */
    public $client;

    /**
     * @var RequestDataBuilder
     */
    private $requestDataHelper;

    /**
     * @var \Magento\Backend\Model\Auth\Session $session
     */
    private $session;

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @var Configuration
     */
    private $taxConfig;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    private $productMetadata;

    /**
     * @var \Magento\Tax\Helper\Data $taxData
     */
    private $taxData;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    private $taxClassRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \stdClass
     */
    private $response;

    /**
     * CyberSourceSoapAPI constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $gatewayConfig
     * @param Configuration $taxConfig
     * @param LoggerInterface $logger
     * @param BuilderInterface $transactionBuilder
     * @param RequestDataBuilder $requestDataHelper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param ProductMetadata $productMetadata
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepositoryInterface
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \SoapClient|null $client
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $gatewayConfig,
        Configuration $taxConfig,
        LoggerInterface $logger,
        BuilderInterface $transactionBuilder,
        RequestDataBuilder $requestDataHelper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Backend\Model\Auth\Session $authSession,
        ProductMetadata $productMetadata,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepositoryInterface,
        \Magento\Checkout\Model\Session $checkoutSession,
        \SoapClient $client = null
    ) {
        parent::__construct($scopeConfig, $logger);

        /**
         * Added soap client as parameter to be able to mock in unit tests.
         */
        if ($client !== null) {
            $this->setSoapClient($client);
        }

        $this->gatewayConfig = $gatewayConfig;
        $this->taxConfig = $taxConfig;

        $this->client = $this->getSoapClient();
        $this->requestDataHelper = $requestDataHelper;
        $this->session = $authSession;
        $this->productMetadata = $productMetadata;
        $this->taxData = $taxData;
        $this->taxClassRepository = $taxClassRepositoryInterface;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Tax calculation for order
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @return $this
     */
    public function getTaxForOrder(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
    ) {
        $address = $shippingAssignment->getShipping()->getAddress();
        $billingAddress = $quote->getBillingAddress();

        if (!$address->getPostcode()) {
            return;
        }

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->partnerSolutionID = 'T54H9OLO';
        $developerId = $this->gatewayConfig->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }

        if ($quote->getReservedOrderId()) {
            $request->merchantReferenceCode = $quote->getReservedOrderId();
        } else {
            $request->merchantReferenceCode = $quote->getId();
        }

        /**
         * Try to add the billingAddress from customer to billTo, when it's not available, use the store address
         * as billing address, since the tax is calculated based on store address (shipFrom)
         */
        $builtBillingAddress = $this->buildAddressForTax($billingAddress);
        if (!is_null($builtBillingAddress)) {
            $request->billTo = $builtBillingAddress;
        } else {
            $request->billTo = $this->buildAddressForTax($address);
        }

        $request->shipTo = $this->buildAddressForTax($address);

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $request->purchaseTotals = $purchaseTotals;

        $shippingCountry = $address->getCountryId();
        if ($shippingCountry == 'CA' || $shippingCountry == 'US') {
            $request->shipFrom = $this->buildStoreShippingFromForTax();
        }

        $taxService = new \stdClass();
        $taxService->run = 'true';

        $nexusRegions = $this->taxConfig->getTaxNexusRegions(" ");
        if (!empty($nexusRegions)) {
            $taxService->nexus = $nexusRegions;
        }

        if ($shippingCountry == 'CA' || $shippingCountry == 'US') {
            $taxService = $this->buildTaxOrderConfigurationForTax($taxService);
        }

        if ($shippingCountry != 'US') {
            $taxService->sellerRegistration = $this->taxConfig->getTaxMerchantVat();
            if ($address->getVatId() != null) {
                $taxService->buyerRegistration = $address->getVatId();
            }
        }

        $request->taxService = $taxService;

        $request->item = $this->buildItemNodeFromShippingItems($quote, $quoteTaxDetails);

        if ($this->orderChanged($request)) {

            $this->setSessionData('request', serialize($request));

            try {
                $isValidShipToAddress = $this->validateAddress($request->shipTo);
                if ($isValidShipToAddress) {
                    $this->logger->info("Tax Request: " . json_encode((array) $request));
                    $response = $this->client->runTransaction($request);
                    $this->response = serialize($response);
                    $this->setSessionData('response', serialize($response));
                    $this->logger->info("Tax response: ". json_encode((array) $response));
                } else {
                    $this->logger->info("Unable to request. Missing shipTo information");
                    $this->response = null;
                    $this->unsetSessionData('response');
                }

            } catch (\Exception $e) {
                $this->response = null;
                $this->unsetSessionData('response');
                $this->logger->error("error in reply tax service: " . $e->getMessage());
            }
        } else {
            $sessionResponse = $this->getSessionData('response');

            if (isset($sessionResponse)) {
                $this->response = $sessionResponse;
            }
        }

        return $this;
    }

    /**
     * Validate response
     *
     * @return bool
     */
    public function isValidResponse()
    {
        $response = unserialize($this->response);

        if (!$response) {
            return false;
        }

        if ($response->reasonCode == self::SUCCESS_REASON_CODE && property_exists($response, 'taxReply')) {
            return true;
        }

        return false;
    }

    /**
     * Verify if request is different than the last one
     *
     * @param  \stdClass $request
     * @return bool
     */
    private function orderChanged($request)
    {
        $sessionRequest = $this->getSessionData('request');

        if ($sessionRequest) {
            return serialize($request) != $sessionRequest;
        } else {
            return true;
        }
    }

    /**
     * Get prefixed session data from checkout/session
     *
     * @param  string $key
     * @return object
     */
    private function getSessionData($key)
    {
        return $this->checkoutSession->getData('cybersource_tax_' . $key);
    }

    /**
     * Set prefixed session data in checkout/session
     *
     * @param  string $key
     * @param  string $val
     * @return object
     */
    private function setSessionData($key, $val)
    {
        return $this->checkoutSession->setData('cybersource_tax_' . $key, $val);
    }

    /**
     * Unset prefixed session data in checkout/session
     *
     * @param  string $key
     * @return object
     */
    private function unsetSessionData($key)
    {
        return $this->checkoutSession->unsetData('cybersource_tax_' . $key);
    }


    /**
     * Get item based on the unit price
     * @TODO Check with CyberSource if there is another identify that can be used rather than unit_price
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterface $itemDataObject
     * @return array
     */
    public function getItemFromResponse(\Magento\Tax\Api\Data\QuoteDetailsItemInterface $itemDataObject)
    {
        if ($this->response !== null && $this->response !== '') {
            $response = unserialize($this->response);

            if (!property_exists($response, 'taxReply') || !property_exists($response->taxReply, 'item')) {
                return null;
            }

            $items = $response->taxReply->item;

            if (is_array($items)) {
                foreach ($items as $item) {
                    if (property_exists($item, 'taxableAmount')) {

                        $unitPrice = $this->getPriceConsideringDiscount($itemDataObject);
                        $linePrice = $unitPrice * $itemDataObject->getQuantity();

                        if ($item->taxableAmount === $this->requestDataHelper->formatAmount($linePrice)) {
                            return (array)$item;
                        }
                    }
                }
            }

            if (is_object($items)) {
                return (array) $items;
            }
        }
    }

    /**
     * Get unit price considering discount
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterface $itemDataObject
     * @return float
     */
    private function getPriceConsideringDiscount(\Magento\Tax\Api\Data\QuoteDetailsItemInterface $itemDataObject)
    {
        $discountAmount = $itemDataObject->getDiscountAmount();
        $itemUnitPrice = $itemDataObject->getUnitPrice();
        $unitPrice = $itemUnitPrice;

        if ($discountAmount != null && $discountAmount > 0) {
            $unitPrice = $itemUnitPrice - ($discountAmount / $itemDataObject->getQuantity());
        }

        return $this->requestDataHelper->formatAmount($unitPrice);
    }

    /**
     * Build order items
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails
     * @return array
     */
    private function buildItemNodeFromShippingItems(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails
    ) {
        $lineItems = [];
        $store = $quote->getStore();
        $items = $quoteTaxDetails->getItems();

        if (count($items) > 0) {
            $parentQuantities = [];

            foreach ($items as $i => $item) {

                if ($item->getType() == 'product') {
                    $lineItem = new \stdClass();
                    $id = $i;
                    $parentId = $item->getParentCode();
                    $quantity = (int) $item->getQuantity();
                    $unitPrice = (float) $item->getUnitPrice();
                    $discount = (float) $item->getDiscountAmount() / $quantity;
                    $extensionAttributes = $item->getExtensionAttributes();
                    $sku = $extensionAttributes->__toArray()['sku'];
                    $productName = $extensionAttributes->__toArray()['product_name'];

                    if ($extensionAttributes->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                        $parentQuantities[$id] = $quantity;

                        if ($extensionAttributes->getPriceType() == \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
                            continue;
                        }
                    }

                    if (isset($parentQuantities[$parentId])) {
                        $quantity *= $parentQuantities[$parentId];
                    }

                    if (!$this->taxData->applyTaxAfterDiscount($store)) {
                        $discount = 0;
                    }

                    if ($item->getTaxClassKey()->getValue()) {
                        $taxClass = $this->taxClassRepository->get($item->getTaxClassKey()->getValue());
                        $taxCode = $taxClass->getClassName();
                    } else {
                        $taxCode = Configuration::TAX_DEFAULT_CODE;
                    }

                    if ($this->productMetadata->getEdition() == 'Enterprise') {
                        if ($extensionAttributes->getProductType() == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD) {
                            $giftTaxClassId = $this->config->getValue('tax/classes/wrapping_tax_class');

                            if ($giftTaxClassId) {
                                $giftTaxClass = $this->taxClassRepository->get($giftTaxClassId);
                                $giftTaxClassCode = $giftTaxClass->getClassName();
                                $taxCode = $giftTaxClassCode;
                            } else {
                                $taxCode = Configuration::TAX_DEFAULT_CODE;
                            }
                        }
                    }

                    $lineItem->id = $id;
                    $lineItem->unitPrice = $this->requestDataHelper->formatAmount($unitPrice - $discount);
                    $lineItem->quantity = (string) $quantity;
                    $lineItem->productCode = $taxCode;
                    $lineItem->productName = $productName;
                    $lineItem->productSKU = $sku;

                    $lineItems[] = $lineItem;
                }
            }
        }

        return $lineItems;
    }

    /**
     * @param Address $address
     * @return \stdClass $builtAddress
     */
    private function buildAddressForTax(\Magento\Quote\Model\Quote\Address $address)
    {
        $builtAddress = new \stdClass();

        if ($address->getCountry() !== null) {
            if ($address->getCountry() == 'CA' || $address->getCountry() == 'US') {
                $builtAddress->state = $address->getRegionCode();
            } else {
                $builtAddress->state = $address->getRegion();
            }
        }

        if ($address->getData(Address::KEY_POSTCODE) !== null) {
            $builtAddress->postalCode = $address->getPostcode();
        }

        if ($address->getData(Address::KEY_FIRSTNAME) !== null) {
            $builtAddress->firstName = $address->getFirstname();
        }

        if ($address->getData(Address::KEY_LASTNAME) !== null) {
            $builtAddress->lastName = $address->getLastname();
        }

        if ($address->getData(Address::KEY_STREET) !== null) {
            $builtAddress->street1 = $address->getStreetLine(1);
            $addressLine2 = $address->getStreetLine(2);
            if ($addressLine2 !== '' && $addressLine2 !== null && $addressLine2 !== $address->getStreetLine(1)) {
                $builtAddress->street2 = $addressLine2;
            }
        }

        if ($address->getData(Address::KEY_CITY) !== null) {
            $builtAddress->city = $address->getCity();
        }

        if ($address->getData(Address::KEY_EMAIL) !== null) {
            $builtAddress->email = $address->getEmail();
        }

        if ($address->getData(Address::KEY_COUNTRY_ID) !== null) {
            $builtAddress->country = $address->getCountryId();
        }

        if ($this->validateAddress($builtAddress)) {
            return $builtAddress;
        }

        return null;
    }

    /**
     * Retrieve Tax Shipping From configuration
     *
     * @return \stdClass
     */
    private function buildStoreShippingFromForTax()
    {
        $shipFrom = new \stdClass();
        $shipFrom->city = $this->taxConfig->getTaxShipFromCity();
        $shipFrom->country = $this->taxConfig->getTaxShipFromCountry();
        $shipFrom->state = $this->taxConfig->getTaxShipFromRegion();
        $shipFrom->postalCode = $this->taxConfig->getTaxShipFromPostcode();

        return $shipFrom;
    }

    /**
     * Build TaxService order node
     *
     * @param \stdClass $taxService
     * @return \stdClass
     */
    private function buildTaxOrderConfigurationForTax(\stdClass $taxService)
    {
        // orderAcceptance
        $taxService->orderAcceptanceCity = $this->taxConfig->getTaxAcceptanceCity();
        $taxService->orderAcceptanceCountry = $this->taxConfig->getTaxAcceptanceCountry();
        $taxService->orderAcceptanceState = $this->taxConfig->getTaxAcceptanceRegion();
        $taxService->orderAcceptancePostalCode = $this->taxConfig->getTaxAcceptancePostcode();

        // orderOrigin
        $taxService->orderOriginCity = $this->taxConfig->getTaxOriginCity();
        $taxService->orderOriginCountry = $this->taxConfig->getTaxOriginCountry();
        $taxService->orderOriginState = $this->taxConfig->getTaxOriginRegion();
        $taxService->orderOriginPostalCode = $this->taxConfig->getTaxOriginPostcode();

        return $taxService;
    }

    /**
     * @param $address
     * @return bool
     */
    public function validateAddress($address)
    {
        if (is_null($address)){
            return false;
        }
        $validationKeys = ['state', 'postalCode', 'country'];

        foreach ($validationKeys as $key) {
            if ((empty($address->{$key}) || $address->{$key} == null)) {
                return false;
            }
        }

        return true;
    }
}
