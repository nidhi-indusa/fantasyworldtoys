<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\PayPal\Helper;

use CyberSource\Core\Helper\AbstractDataBuilder;
use CyberSource\Core\Model\ConfigProvider;
use CyberSource\PayPal\Model\Config;
use CyberSource\Tax\Model\Tax\Sales\Total\Quote\Tax;
use CyberSource\Tax\Service\CyberSourceSoapAPI;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Type\Onepage;

class RequestDataBuilder extends AbstractDataBuilder
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Tax
     */
    private $taxService;

    /**
     * @var \Magento\Quote\Api\Data\ShippingAssignmentInterface
     */
    private $shippingAssignment;

    /**
     * @var \Magento\Quote\Model\Shipping
     */
    private $quoteShipping;

    /**
     * @var \Magento\Quote\Model\Quote\Address\Total
     */
    private $total;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;


    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $config,
        Session $checkoutSession,
        Tax $taxService,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total,
        \Magento\Quote\Model\Shipping $quoteShipping,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,

        \Magento\Checkout\Helper\Data $data
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->storeManager = $storeManager;
        $this->taxService = $taxService;
        $this->shippingAssignment = $shippingAssignment;
        $this->total = $total;
        $this->quoteShipping = $quoteShipping;
        $this->quoteRepository = $quoteRepository;
        $quote = $checkoutSession->getQuote();
        $this->config = $config;
        $this->config->setStoreId($quote->getStoreId());

        $this->setUpCredentials($config->getPayPalMerchantId(), $config->getTransactionKey());
    }

    public function buildSetService(\Magento\Quote\Model\Quote $quote, $returnUrl, $cancelUrl)
    {
        $request = new \stdClass();

        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $this->config->setMethodCode(ConfigProvider::CODE);
        $developerId = $this->config->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $this->config->setMethodCode(\CyberSource\PayPal\Model\Ui\ConfigProvider::PAYPAL_CODE);
        $request->merchantReferenceCode = $quote->getReservedOrderId();

        $payPalEcSetService = new \stdClass();
        $payPalEcSetService->run = "true";
        $payPalEcSetService->paypalReturn = $returnUrl;
        $payPalEcSetService->paypalCancelReturn = $cancelUrl;
        $payPalEcSetService->requestBillingAddress = "0";

        $request = $this->buildRequestItems($quote->getAllVisibleItems(), $request);
        $request->customerID = (!empty($this->checkoutSession->getGuestEmail())) ? $this->customerSession->getCustomerId() : 'guest';

        $request->payPalEcSetService =  $payPalEcSetService;

        $request->requestBillingAddress = "1";

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $request->purchaseTotals = $purchaseTotals;

        return $request;
    }

    public function buildGetDetailsService($setServiceResponse)
    {
        $request = new \stdClass();
        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $this->config->setMethodCode(ConfigProvider::CODE);
        $developerId = $this->config->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $this->config->setMethodCode(\CyberSource\PayPal\Model\Ui\ConfigProvider::PAYPAL_CODE);
        $request->merchantReferenceCode = $setServiceResponse['merchantReferenceCode'];

        $payPalEcGetDetailsService = new \stdClass();
        $payPalEcGetDetailsService->run = "true";
        $payPalEcGetDetailsService->paypalToken = $setServiceResponse['paypalToken'];
        $payPalEcGetDetailsService->paypalEcSetRequestID = $setServiceResponse['requestID'];
        $payPalEcGetDetailsService->paypalEcSetRequestToken = $setServiceResponse['requestToken'];

        $request->payPalEcGetDetailsService = $payPalEcGetDetailsService;

        return $request;
    }

    public function buildDoPaymentService($getDetailsResponse)
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->reserveOrderId();
        $this->checkoutSession->replaceQuote($quote);
        $billingAddress = $quote->getBillingAddress();

        $request = new \stdClass();
        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $this->config->setMethodCode(ConfigProvider::CODE);
        $developerId = $this->config->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $this->config->setMethodCode(\CyberSource\PayPal\Model\Ui\ConfigProvider::PAYPAL_CODE);
        $request->merchantReferenceCode = $quote->getReservedOrderId();

        $request->billTo = $this->buildAddress($billingAddress, $quote->getCustomerEmail());

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($quote->getGrandTotal());
        $request->purchaseTotals = $purchaseTotals;

        $payPalEcDoPaymentService = new \stdClass();
        $payPalEcDoPaymentService->run = "true";
        $payPalEcDoPaymentService->paypalToken = $getDetailsResponse->payPalEcGetDetailsReply->paypalToken;
        $payPalEcDoPaymentService->paypalPayerId = $getDetailsResponse->payPalEcGetDetailsReply->payerId;
        $payPalEcDoPaymentService->paypalEcSetRequestID = $getDetailsResponse->requestID;
        $payPalEcDoPaymentService->paypalEcSetRequestToken = $getDetailsResponse->requestToken;
        $payPalEcDoPaymentService->paypalCustomerEmail = $getDetailsResponse->payPalEcGetDetailsReply->payer;

        $request = $this->buildRequestItems($quote->getAllItems(), $request);

        $request->payPalEcDoPaymentService = $payPalEcDoPaymentService;

        return $request;
    }

    public function buildOrderSetupService($getDetailsResponse, $quote)
    {
        $request = new \stdClass();
        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $this->config->setMethodCode(ConfigProvider::CODE);
        $developerId = $this->config->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $this->config->setMethodCode(\CyberSource\PayPal\Model\Ui\ConfigProvider::PAYPAL_CODE);
        $request->merchantReferenceCode = $getDetailsResponse['merchantReferenceCode'];

        $payPalEcOrderSetupService = new \stdClass();
        $payPalEcOrderSetupService->run = "true";
        $payPalEcOrderSetupService->paypalToken = $getDetailsResponse['paypalToken'];
        $payPalEcOrderSetupService->paypalPayerId = $getDetailsResponse['paypalPayerId'];
        $payPalEcOrderSetupService->paypalEcSetRequestID = $getDetailsResponse['paypalEcSetRequestID'];
        $payPalEcOrderSetupService->paypalEcSetRequestToken = $getDetailsResponse['paypalEcSetRequestToken'];
        $payPalEcOrderSetupService->paypalCustomerEmail = $getDetailsResponse['paypalCustomerEmail'];

        $request->billTo = $this->buildAddress($quote->getBillingAddress());
        $request->shipTo = $this->buildAddress($quote->getShippingAddress());

        $request = $this->buildRequestItems($quote->getAllVisibleItems(), $request);

        $request->customerID = (!empty($this->checkoutSession->getGuestEmail())) ? $this->customerSession->getCustomerId() : 'guest';
        $request->payPalEcOrderSetupService = $payPalEcOrderSetupService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $request->purchaseTotals = $purchaseTotals;

        return $request;
    }

    public function buildAuthorizationService($orderSetupResponse, $quote, $customerEmail)
    {
        $request = new \stdClass();
        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $this->config->setMethodCode(ConfigProvider::CODE);
        $developerId = $this->config->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $this->config->setMethodCode(\CyberSource\PayPal\Model\Ui\ConfigProvider::PAYPAL_CODE);
        $request->merchantReferenceCode = $quote->getReservedOrderId();

        $payPalAuthorizationService = new \stdClass();
        $payPalAuthorizationService->run = "true";
        $payPalAuthorizationService->paypalOrderId = $orderSetupResponse->payPalEcOrderSetupReply->transactionId;
        $payPalAuthorizationService->paypalCustomerEmail = $customerEmail;
        $payPalAuthorizationService->paypalEcOrderSetupRequestID = $orderSetupResponse->requestID;
        $payPalAuthorizationService->paypalEcOrderSetupRequestToken = $orderSetupResponse->requestToken;

        $request = $this->buildRequestItems($quote->getAllVisibleItems(), $request);
        $request->customerID = (!empty($this->checkoutSession->getGuestEmail())) ? $this->customerSession->getCustomerId() : 'guest';

        $request->payPalAuthorizationService = $payPalAuthorizationService;

        $request->billTo = $this->buildAddress($quote->getBillingAddress());
        $request->shipTo = $this->buildAddress($quote->getShippingAddress());

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $request->purchaseTotals = $purchaseTotals;

        return $request;
    }

    public function buildCaptureService(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $additionalData = unserialize($payment->getAdditionalInformation('authorize'));
        $authorizedItems = unserialize($payment->getAdditionalInformation('authorized_items'));

        $request = new \stdClass();
        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $this->config->setMethodCode(ConfigProvider::CODE);
        $developerId = $this->config->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $this->config->setMethodCode(\CyberSource\PayPal\Model\Ui\ConfigProvider::PAYPAL_CODE);
        $request->merchantReferenceCode = $additionalData->merchantReferenceCode;

        $payPalDoCaptureService = new \stdClass();
        $payPalDoCaptureService->run = "true";
        $payPalDoCaptureService->completeType = ($payment->getAmountAuthorized() == (float) $amount) ?
            "Complete" :
            "NotComplete";
        $payPalDoCaptureService->paypalAuthorizationId = $additionalData->payPalAuthorizationReply->transactionId;
        $payPalDoCaptureService->paypalAuthorizationRequestID = $additionalData->requestID;
        $payPalDoCaptureService->paypalAuthorizationRequestToken = $additionalData->requestToken;

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $request = $this->buildRequestItems($payment->getOrder()->getAllItems(), $request);
        $request->customerID = $payment->getOrder()->getRemoteIp();

        $request->shipTo = $this->buildAddress($payment->getOrder()->getShippingAddress(), $payment->getOrder()->getCustomerEmail());
        $request->billTo = $this->buildAddress($payment->getOrder()->getBillingAddress(), $payment->getOrder()->getCustomerEmail());

        $request->payPalDoCaptureService = $payPalDoCaptureService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $additionalData->payPalAuthorizationReply->currency;
        $purchaseTotals->grandTotalAmount = $this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $request->item = $authorizedItems;

        return $request;
    }

    public function buildRefundService(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $additionalData = unserialize($payment->getAdditionalInformation('capture'));
        $authorizedItems = unserialize($payment->getAdditionalInformation('authorized_items'));

        $request = new \stdClass();
        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $this->config->setMethodCode(ConfigProvider::CODE);
        $developerId = $this->config->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $this->config->setMethodCode(\CyberSource\PayPal\Model\Ui\ConfigProvider::PAYPAL_CODE);
        $request->merchantReferenceCode = $additionalData->merchantReferenceCode;

        $payPalRefundService = new \stdClass();
        $payPalRefundService->run = "true";
        $payPalRefundService->paypalCaptureId = $additionalData->payPalDoCaptureReply->transactionId;
        $payPalRefundService->paypalDoCaptureRequestID = $additionalData->requestID;
        $payPalRefundService->paypalDoCaptureRequestToken = $additionalData->requestToken;

        $request->payPalRefundService = $payPalRefundService;

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $request->customerID = $payment->getOrder()->getRemoteIp();
        $request->shipTo = $this->buildAddress($payment->getOrder()->getShippingAddress(), $payment->getOrder()->getCustomerEmail());
        $request->billTo = $this->buildAddress($payment->getOrder()->getBillingAddress(), $payment->getOrder()->getCustomerEmail());

        $purchaseTotals = new \stdClass();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $request->item = $authorizedItems;

        return $request;
    }

    public function buildAuthorizeReversal(\Magento\Payment\Model\InfoInterface $payment)
    {
        $additionalData = unserialize($payment->getAdditionalInformation('authorize'));
        $authorizedItems = unserialize($payment->getAdditionalInformation('authorized_items'));

        $request = new \stdClass();
        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';
        $this->config->setMethodCode(ConfigProvider::CODE);
        $developerId = $this->config->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $this->config->setMethodCode(\CyberSource\PayPal\Model\Ui\ConfigProvider::PAYPAL_CODE);
        $request->merchantReferenceCode = $additionalData->merchantReferenceCode;

        $payPalAuthReversalService = new \stdClass();
        $payPalAuthReversalService->run = "true";
        $payPalAuthReversalService->paypalAuthorizationId = $additionalData->payPalAuthorizationReply->transactionId;
        $payPalAuthReversalService->paypalAuthorizationRequestID = $additionalData->requestID;
        $payPalAuthReversalService->paypalAuthorizationRequestToken = $additionalData->requestToken;

        $request->payPalAuthReversalService = $payPalAuthReversalService;

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $request->customerID = $payment->getOrder()->getRemoteIp();
        $request->shipTo = $this->buildAddress($payment->getOrder()->getShippingAddress(), $payment->getOrder()->getCustomerEmail());
        $request->billTo = $this->buildAddress($payment->getOrder()->getBillingAddress(), $payment->getOrder()->getCustomerEmail());

        $request->item = $authorizedItems;

        return $request;
    }

    /**
     * @param $quoteAddress
     * @return \stdClass
     */
    private function buildAddress($quoteAddress)
    {
        $address = new \stdClass();
        $address->city =  $quoteAddress->getData('city');
        $address->country = $quoteAddress->getData('country_id');
        $address->postalCode = $quoteAddress->getData('postcode');
        $address->state = $quoteAddress->getRegionCode();
        $address->street1 = $quoteAddress->getStreetLine(1);
        $address->email = $quoteAddress->getEmail();
        $address->firstName = $quoteAddress->getFirstname();
        $address->lastName = $quoteAddress->getLastname();

        if ($quoteAddress->getAddressType() == Quote\Address::TYPE_BILLING) {
            $address->ipAddress = $this->_remoteAddress->getRemoteAddress();
        }

        return $address;
    }

    /**
     * @param array $items
     * @param \stdClass $request
     * @return mixed
     */
    private function buildRequestItems(array $items, \stdClass $request)
    {
        $index = 0;
        foreach ($items as $i => $item) {
            $qty = $item->getQty();
            if (empty($qty)) {
                $qty = 1;
            }
            $amount = ($item->getPrice() - ($item->getDiscountAmount() / $qty));
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $qty;
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->formatAmount($amount);
            $requestItem->taxAmount = $this->formatAmount($item->getTaxAmount());
            $request->item[] = $requestItem;
            $index = $i;
        }

        $shippingCost = $this->checkoutSession->getQuote()->getShippingAddress()->getBaseShippingAmount();
        $shippingCostItem = new \stdClass();
        $shippingCostItem->id = $index + 1;
        $shippingCostItem->productCode = 'shipping_and_handling';
        $shippingCostItem->unitPrice = $this->formatAmount($shippingCost);
        $request->item[] = $shippingCostItem;

        if (property_exists($request, 'item') && is_array($request->item)) {
            foreach ($request->item as $key => $item) {
                if ($item->unitPrice == 0) {
                    unset($request->item[$key]);
                }
            }

            $request->item = array_values($request->item);
        }

        return $request;
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
