<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Request;

use CyberSource\ECheck\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class AuthorizationRequest extends AbstractRequest implements BuilderInterface
{
    /**
     * Builds request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        $request = $this->buildAuthNodeRequest($this->config->getMerchantId(), $order->getOrderIncrementId());

        $ecDebitService = new \stdClass();
        $ecDebitService->run = "true";
        $request->ecDebitService = $ecDebitService;

        $request->billTo = $this->buildBillingAddress($order->getBillingAddress(), $this->remoteAddress->getRemoteAddress());

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $order->getCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($order->getGrandTotalAmount());
        $request->purchaseTotals = $purchaseTotals;

        $bankTransitNumber = $payment->getAdditionalInformation('check_bank_transit_number');
        $accountNumber = $payment->getAdditionalInformation('check_account_number');

        $request->check = $this->buildAccountNode($bankTransitNumber, $accountNumber);

        $request = $this->buildItemsNode($request, $order->getItems());

        return (array) $request;
    }
}
