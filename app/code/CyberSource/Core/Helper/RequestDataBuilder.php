<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Helper;

use CyberSource\Core\Model\Config;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\Context;

class RequestDataBuilder extends AbstractDataBuilder
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    public $gatewayConfig;

    /**
     * RequestDataBuilder constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param SessionManagerInterface $checkoutSession
     * @param SessionManagerInterface $customerSession
     * @param \Magento\Checkout\Helper\Data $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $config,
        \Magento\Checkout\Model\Session $checkoutSession,
        SessionManagerInterface $customerSession,
        \Magento\Checkout\Helper\Data $data
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->storeManager = $storeManager;
        $this->gatewayConfig = $config;
        $this->setUpCredentials(
            $config->getMerchantId(),
            $config->getTransactionKey()
        );
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @param $tokenData
     * @param null $quote
     * @param bool $isAdmin
     * @param null $amount
     * @param bool $dmEnabled
     * @param bool $isCaptureRequest
     * @return \stdClass
     */
    public function buildTokenPaymentData(
        $tokenData,
        $quote = null,
        $isAdmin = false,
        $amount = null,
        $dmEnabled = true,
        $isCaptureRequest = false
    ) {
        $quote = (!empty($quote)) ? $quote : $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();

        $request = new \stdClass();

        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';

        if (!empty($this->gatewayConfig->getDeveloperId())) {
            $request->developerId = $this->gatewayConfig->getDeveloperId();
        }
        $quote->reserveOrderId();
        $request->merchantReferenceCode = $quote->getReservedOrderId();

        $request->clientLibrary = "PHP";
        $request->clientLibraryVersion = phpversion();

        $ccAuthService = new \stdClass();
        $ccAuthService->run = "true";
        if ($isAdmin) {
            $ccAuthService->commerceIndicator = "moto";
        }
        if ((string) $this->gatewayConfig->getAuthIndicator() != 2) {
            $ccAuthService->authIndicator = (string) $this->gatewayConfig->getAuthIndicator();
        }

        $ccAuthService->reconciliationID = $quote->getReservedOrderId();

        $request->ccAuthService = $ccAuthService;

        if ($isCaptureRequest) {
            $ccCaptureService = new \stdClass();
            $ccCaptureService->run = "true";
            $request->ccCaptureService = $ccCaptureService;
        }

        if (array_key_exists('cvv', $tokenData)) {
            $card = new \stdClass();
            $card->cvNumber = $tokenData['cvv'];
            $card->cvIndicator = '1';
            $request->card = $card;
        }

        $recurringSubscriptionInfo = new \stdClass();
        $recurringSubscriptionInfo->subscriptionID = $tokenData['payment_token'];
        $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $purchaseTotals->grandTotalAmount = (!empty($amount)) ? $amount : $this->formatAmount($quote->getGrandTotal());
        $request->purchaseTotals = $purchaseTotals;

        if (!$dmEnabled) {
            $dm = new \stdClass();
            $dm->enabled = 'false';
            $request->decisionManager = $dm;
        }

        foreach ($quote->getAllItems() as $i => $item) {
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $item->getQty();
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->formatAmount($item->getPrice());
            $requestItem->taxAmount = $this->formatAmount($item->getTaxAmount());
            $request->item[] = $requestItem;
        }

        return $request;
    }

    /**
     * @param $tokenData
     * @param \Magento\Sales\Model\Order $order
     * @param bool $isAdmin
     * @param null $amount
     * @param bool $dmEnabled
     * @param bool $isCaptureRequest
     * @return \stdClass
     */
    public function buildTokenPaymentDataFromOrder(
        $tokenData,
        \Magento\Sales\Model\Order $order,
        $isAdmin = false,
        $amount = null,
        $dmEnabled = true,
        $isCaptureRequest = false,
        $isAuthorizedPayment = false
    ) {
        $request = new \stdClass();

        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = 'T54H9OLO';

        if (!empty($this->gatewayConfig->getDeveloperId())) {
            $request->developerId = $this->gatewayConfig->getDeveloperId();
        }
        $request->merchantReferenceCode = $order->getIncrementId();

        $request->clientLibrary = "PHP";
        $request->clientLibraryVersion = phpversion();

        if (!$isAuthorizedPayment) {
            $ccAuthService = new \stdClass();
            $ccAuthService->run = "true";
            if ($isAdmin) {
                $ccAuthService->commerceIndicator = "moto";
            }
            if ((string) $this->gatewayConfig->getAuthIndicator() != 2) {
                $ccAuthService->authIndicator = (string) $this->gatewayConfig->getAuthIndicator();
            }

            $ccAuthService->reconciliationID = $order->getIncrementId();

            $request->ccAuthService = $ccAuthService;
        }

        if ($isCaptureRequest) {
            $ccCaptureService = new \stdClass();
            $ccCaptureService->run = "true";
            $request->ccCaptureService = $ccCaptureService;
        }

        if (array_key_exists('cvv', $tokenData)) {
            $card = new \stdClass();
            $card->cvNumber = $tokenData['cvv'];
            $card->cvIndicator = '1';
            $request->card = $card;
        }

        $recurringSubscriptionInfo = new \stdClass();
        $recurringSubscriptionInfo->subscriptionID = $tokenData['payment_token'];
        $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $order->getOrderCurrency()->getCode();
        $purchaseTotals->grandTotalAmount = (!empty($amount)) ? $amount : $this->formatAmount($order->getGrandTotal());
        $request->purchaseTotals = $purchaseTotals;

        $shippingAddress = $order->getShippingAddress();
        $request->shipTo = $this->buildAddress($shippingAddress, $order->getCustomerEmail());

        if (!$dmEnabled) {
            $dm = new \stdClass();
            $dm->enabled = 'false';
            $request->decisionManager = $dm;
        }

        foreach ($order->getAllItems() as $i => $item) {
            $requestItem = new \stdClass();
            $requestItem->id = $item->getProductId();
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $item->getQtyOrdered();
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->formatAmount($item->getPrice());
            $requestItem->taxAmount = $this->formatAmount($item->getTaxAmount());
            $request->item[] = $requestItem;
        }

        foreach ($request->item as $key => $item) {
            if ($item->unitPrice == 0) {
                unset($request->item[$key]);
            }
        }

        $request->item = array_values($request->item);

        return $request;
    }
  
    /**
     * Build request to create token by transaction id
     *
     * @param array $data
     * @return \stdClass
     */
    public function buildTokenByTransaction($data)
    {
        $request = new \stdClass();

        $request->merchantID = $data['merchant_id'];
        $request->partnerSolutionID = 'T54H9OLO';
        if (!empty($this->gatewayConfig->getDeveloperId())) {
            $request->developerId = $this->gatewayConfig->getDeveloperId();
        }
        $request->merchantReferenceCode = $data['ref_id'];

        $request->clientLibrary = "PHP";
        $request->clientLibraryVersion = phpversion();

        $paySubscriptionCreateService = new \stdClass();
        $paySubscriptionCreateService->run = "true";
        $paySubscriptionCreateService->paymentRequestID = $data['request_id'];
        $request->paySubscriptionCreateService = $paySubscriptionCreateService;

        $recurringSubscriptionInfo = new \stdClass();
        $recurringSubscriptionInfo->frequency = 'on-demand';
        $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

        return $request;
    }

    /**
     * @param $quoteAddress
     * @return \stdClass
     */
    private function buildAddress($quoteAddress, $customerEmail)
    {
        $address = new \stdClass();
        $address->city =  $quoteAddress->getData('city');
        $address->country = $quoteAddress->getData('country_id');
        $address->postalCode = $quoteAddress->getData('postcode');
        $address->state = $quoteAddress->getRegionCode();
        $address->street1 = $quoteAddress->getStreetLine(1);
        $address->email = $customerEmail;
        $address->firstName = $quoteAddress->getFirstname();
        $address->lastName = $quoteAddress->getLastname();

        if ($quoteAddress->getAddressType() == Address::TYPE_BILLING) {
            $address->ipAddress = $this->_remoteAddress->getRemoteAddress();
        }

        return $address;
    }
}
