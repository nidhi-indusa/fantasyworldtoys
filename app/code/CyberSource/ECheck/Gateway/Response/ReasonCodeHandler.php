<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class ReasonCodeHandler implements HandlerInterface
{
    const MERCHANT_REFERENCE_CODE = "merchantReferenceCode";
    const REQUEST_ID = "requestID";
    const DECISION = "decision";
    const REASON_CODE = "reasonCode";
    const REQUEST_TOKEN = "requestToken";

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response[self::REQUEST_ID]);
        $payment->setAdditionalInformation(self::MERCHANT_REFERENCE_CODE, $response[self::MERCHANT_REFERENCE_CODE]);
        $payment->setAdditionalInformation(self::DECISION, $response[self::DECISION]);
        $payment->setAdditionalInformation(self::REASON_CODE, $response[self::REASON_CODE]);
        $payment->setAdditionalInformation(self::REQUEST_TOKEN, $response[self::REQUEST_TOKEN]);
        $payment->setAdditionalInformation(self::REQUEST_ID, $response[self::REQUEST_ID]);

        $payment->setIsTransactionClosed(false);
        $payment->setShouldCloseParentTransaction(false);
    }
}
