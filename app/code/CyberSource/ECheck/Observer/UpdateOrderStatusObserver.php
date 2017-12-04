<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class UpdateOrderStatusObserver implements ObserverInterface
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

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getData('order');
        if ($order->getPayment()->getMethod() == 'cybersourceecheck') {
            $order->setStatus(Order::STATE_PENDING_PAYMENT);
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $this->_orderRepository->save($order);
        }
    }
}
