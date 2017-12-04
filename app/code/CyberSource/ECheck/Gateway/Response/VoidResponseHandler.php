<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;

class VoidResponseHandler implements HandlerInterface
{
    const MERCHANT_REFERENCE_CODE = "merchantReferenceCode";
    const REQUEST_ID = "requestID";
    const DECISION = "decision";
    const REASON_CODE = "reasonCode";
    const REQUEST_TOKEN = "requestToken";

    /**
     * @var \Magento\Sales\Model\OrderRepository $orderRepository
     */
    protected $_orderRepository;

    public function __construct(
        \Magento\Sales\Model\OrderRepository $orderRepository
    ) {
        $this->_orderRepository = $orderRepository;
    }

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

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentDO->getPayment();

        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        $payment->setIsTransactionPending(false);

        $order = $payment->getOrder();

        $order->setState(Order::STATE_CLOSED);
        $order->setStatus(Order::STATE_CLOSED);

        $this->_orderRepository->save($order);
    }
}
