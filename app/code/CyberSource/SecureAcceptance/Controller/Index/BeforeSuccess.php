<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

use Psr\Log\LoggerInterface;

class BeforeSuccess extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * BeforeSuccess constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Session\SessionManagerInterface $checkoutSession
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Session\SessionManagerInterface $checkoutSession,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        parent::__construct($context);
    }
    
    public function execute()
    {
        $quoteId = $this->_request->getParam('quote_id');
        $orderId = $this->_request->getParam('order_id');
        $realOrderId = $this->_request->getParam('real_order_id');
        $orderStatus = $this->_request->getParam('order_status');
        $this->checkoutSession->setLastSuccessQuoteId($quoteId);
        $this->checkoutSession->setLastQuoteId($quoteId);
        $this->checkoutSession->setLastOrderId($orderId);
        $this->checkoutSession->setLastOrderStatus($orderStatus);
        $this->checkoutSession->setLastRealOrderId($realOrderId);
        $this->cart->truncate()->save();
        $this->messageManager->addSuccessMessage(__('Your order has been successfully created !'));
        $this->_redirect('checkout/onepage/success');
    }
}
