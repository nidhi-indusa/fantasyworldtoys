<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;

class UpdateOrderStatusHandler implements HandlerInterface
{

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
            || !$handlingSubject['payment'] instanceof OrderPaymentInterface
        ) {
            throw new \InvalidArgumentException('OrderPaymentInterface should be provided');
        }

        $payment = $handlingSubject['payment'];

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus(Order::STATE_PROCESSING);

        $this->_orderRepository->save($order);

        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending(false);
    }
}
