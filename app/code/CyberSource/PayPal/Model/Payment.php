<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\PayPal\Model;

use CyberSource\PayPal\Service\CyberSourcePayPalSoapAPI;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use CyberSource\PayPal\Helper\RequestDataBuilder;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Model\Order;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'cybersourcepaypal';

    protected $_code = self::CODE;
    protected $_canAuthorize = true;
    protected $_isOffline = false;
    protected $_isGateway                   = true;
    protected $_canUseInternal              = false;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;

    /**
     * @var CyberSourcePayPalSoapAPI|null
     */
    private $gatewayAPI = null;

    /**
     * @var RequestDataBuilder
     */
    private $helper;

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Payment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param CyberSourcePayPalSoapAPI $cyberSourceAPI
     * @param Session $checkoutSession
     * @param Config $gatewayConfig
     * @param RequestDataBuilder $dataBuilder
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        CyberSourcePayPalSoapAPI $cyberSourceAPI,
        Session $checkoutSession,
        Config $gatewayConfig,
        RequestDataBuilder $dataBuilder,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->gatewayAPI = $cyberSourceAPI;
        $this->gatewayConfig = $gatewayConfig;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $dataBuilder;
        $this->transactionBuilder = $transactionBuilder;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     * @deprecated 100.2.0
     */
    public function getTitle()
    {
        return $this->gatewayConfig->getTitle();
    }

    /**
     * Assign data to info model instance
     *
     * @param array|\Magento\Framework\DataObject $data
     * @return \Magento\Payment\Model\Info
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return $this;
        }

        foreach ($additionalData as $key => $value) {
            $this->getInfoInstance()->setAdditionalInformation($key, $value);
        }
        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::authorize($payment, $amount);

        $payment->setIsTransactionClosed(0);

        if ($payment->getAdditionalInformation('pending_reason') == 100) {
            $payment->getOrder()->setState(Order::STATE_PENDING_PAYMENT);
            $payment->getOrder()->setStatus(Order::STATE_PENDING_PAYMENT);
        }

        if ($payment->getAdditionalInformation('pending_reason') == 480) {
            $payment->setIsFraudDetected(true);
            $payment->setIsTransactionPending(true);
        }

        $this->updateOrderInformation($payment);
        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->updateOrderInformation($payment);
        $order = $payment->getOrder();

        if ($payment->getAdditionalInformation('is_fraud_detected')) {
            $payment->setIsTransactionClosed(0);
            $payment->setIsTransactionPending(true);
            $payment->setIsFraudDetected(true);

            $formattedPrice = $order->getBaseCurrency()->formatTxt($amount);
            $message = __('The ordering amount of %1 is pending approval on the payment gateway.', $formattedPrice);

            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($payment->getTransactionId())
                ->build(Order\Payment\Transaction::TYPE_ORDER);
            $payment->addTransactionCommentsToOrder($transaction, $message);

            $payment->unsAdditionalInformation('is_fraud_detected');

            return $this;
        }

        $payment->setTransactionId($payment->getAdditionalInformation('cc_trans_id'));

        try {
            $request = $this->helper->buildCaptureService($payment, $amount);

            $this->gatewayAPI->setPayment($payment);
            $response = $this->gatewayAPI->captureService($request);

            if ($response !== null) {
                $payment->setAdditionalInformation('capture', serialize($response));
                $payment->setIsTransactionPending(false);
                $payment->setIsFraudDetected(false);
                $payment->setIsTransactionClosed(1);
                $order->setStatus(Order::STATE_PROCESSING);
                $order->setState(Order::STATE_PROCESSING);
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__('Sorry but your transaction was unsuccessful.'));
        }

        return $this;
    }

    private function updateOrderInformation($payment)
    {
        $authorizationResponse = unserialize($payment->getAdditionalInformation('authorize'));

        $payment->setMethod(Payment::CODE);
        $payment->setLastTransId($authorizationResponse->payPalAuthorizationReply->transactionId);
        $payment->setTransactionId($authorizationResponse->payPalAuthorizationReply->transactionId);
        $payment->setCcTransId($authorizationResponse->requestID);
    }

    /**
     * Void Captured Payment. Try to perform cancel if void fail
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @throws LocalizedException
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this;
    }

    /**
     * Cancel a payment and reverse authorization at CyberSource
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @throws LocalizedException
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $request = $this->helper->buildAuthorizeReversal($payment);

            $this->gatewayAPI->setPayment($payment);
            $response = $this->gatewayAPI->authorizeReversalService($request);

            if ($response !== null) {
                $payment->setAdditionalInformation('reversal', serialize($response));
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__('Sorry but your transaction was unsuccessful.'));
        }

        return $this;
    }

    /**
     * Perform a refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount < $payment->getBaseAmountPaidOnline()) {
            $amount = $payment->getBaseAmountPaidOnline();
        }

        try {
            $request = $this->helper->buildRefundService($payment, $amount);

            $this->gatewayAPI->setPayment($payment);
            $this->gatewayAPI->refundService($request);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @see \Magento\Sales\Model\Payment::place()
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return $this->gatewayConfig->getPaymentAction();
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return $this->urlBuilder->getUrl('cybersourcepaypal/express/start');
    }

    /**
     * Check whether payment method can be used
     * @param \Magento\Quote\Api\Data\CartInterface|Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote) && $this->gatewayConfig->isActive();
    }
}
