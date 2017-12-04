<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace CyberSource\SecureAcceptance\Model\Checkout\Type;

use CyberSource\SecureAcceptance\Model\Source\PaymentAction;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Invoice;

/**
 * Multishipping checkout model
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Multishipping extends \Magento\Multishipping\Model\Checkout\Type\Multishipping
{
    /**
     * @var \Magento\Sales\Model\Order\Status $status
     */
    private $status;

    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    
    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;
    
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Session\Generic $session,
        \Magento\Quote\Model\Quote\AddressFactory $addressFactory,
        \Magento\Quote\Model\Quote\Address\ToOrder $quoteAddressToOrder,
        \Magento\Quote\Model\Quote\Address\ToOrderAddress $quoteAddressToOrderAddress,
        \Magento\Quote\Model\Quote\Payment\ToOrderPayment $quotePaymentToOrderPayment,
        \Magento\Quote\Model\Quote\Item\ToOrderItem $quoteItemToOrderItem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Payment\Model\Method\SpecificationInterface $paymentSpecification,
        \Magento\Multishipping\Helper\Data $helper,
        OrderSender $orderSender,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Sales\Model\Order\Status $status,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Order\InvoiceRepository $invoiceRepository,
        array $data = []
    ) {
        parent::__construct(
            $checkoutSession,
            $customerSession,
            $orderFactory,
            $addressRepository,
            $eventManager,
            $scopeConfig,
            $session,
            $addressFactory,
            $quoteAddressToOrder,
            $quoteAddressToOrderAddress,
            $quotePaymentToOrderPayment,
            $quoteItemToOrderItem,
            $storeManager,
            $paymentSpecification,
            $helper,
            $orderSender,
            $priceCurrency,
            $quoteRepository,
            $searchCriteriaBuilder,
            $filterBuilder,
            $totalsCollector,
            $data
        );

        $this->status = $status;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * Create orders per each quote address
     *
     * @return \Magento\Multishipping\Model\Checkout\Type\Multishipping
     * @throws \Exception
     */
    public function createOrders()
    {
        $orderIds = [];
        $this->_validate();
        $shippingAddresses = $this->getQuote()->getAllShippingAddresses();
        $orders = [];

        $paymentAction = $this->_scopeConfig->getValue(
            "payment/chcybersource/payment_action",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($this->getQuote()->hasVirtualItems()) {
            $shippingAddresses[] = $this->getQuote()->getBillingAddress();
        }

        try {
            foreach ($shippingAddresses as $address) {
                $order = $this->_prepareOrder($address);

                $orders[] = $order;
                $this->_eventManager->dispatch(
                    'checkout_type_multishipping_create_orders_single',
                    ['order' => $order, 'address' => $address, 'quote' => $this->getQuote()]
                );
            }

            foreach ($orders as $order) {
                $this->logger->info('before placed order');
                $order->place();
                $this->logger->info('placed order');
                $reasonCode = $order->getPayment()->getAdditionalInformation('reasonCode');
                if (empty($reasonCode) || !in_array($reasonCode, [100, 480])) {
                    $this->updateInvoiceState($order, 'REJECT');
                } if ($reasonCode == 480) {
                    $order->setState('payment_review');
                    $order->setStatus($this->getStatusByState('payment_review'));
                    $this->updateInvoiceState($order, $reasonCode);
                } else if ($reasonCode == 100 && $paymentAction === \CyberSource\SecureAcceptance\Model\Payment::ACTION_AUTHORIZE) {
                    $order->setState('pending_payment');
                    $order->setStatus($this->status->loadDefaultByState('pending_payment')->getStatus());
                }

                $order->save();
                if ($order->getCanSendNewEmailFlag()) {
                    $this->orderSender->send($order);
                }
                $orderIds[$order->getId()] = $order->getIncrementId();
            }

            $this->_session->setOrderIds($orderIds);
            $this->_checkoutSession->setLastQuoteId($this->getQuote()->getId());

            $this->getQuote()->setIsActive(false);
            $this->quoteRepository->save($this->getQuote());

            $this->_eventManager->dispatch(
                'checkout_submit_all_after',
                ['orders' => $orders, 'quote' => $this->getQuote()]
            );

            return $this;
        } catch (\Exception $e) {
            $this->_eventManager->dispatch('checkout_multishipping_refund_all', ['orders' => $orders]);
            throw $e;
        }
    }
    
    
    /**
     * Returns any possible status for state
     *
     * @param string $state
     * @return string
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    private function getStatusByState($state)
    {
        return $this->status->loadDefaultByState($state)->getStatus();
    }
    
    /**
     * 
     * @param Order $order
     * @param string $decision
     * @return type
     */
    private function updateInvoiceState($order, $decision)
    {
        if($this->registry->registry('isSecureArea')){
            $this->registry->unregister('isSecureArea');
        }
        $this->registry->register('isSecureArea', true);

        /** @var \Magento\Sales\Model\Order $order */
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        $this->logger->info('step 1');
        /**
         * When module is configured to auth only and payment is caught by DM
         * there is no invoice to be updated, so we just return
         */
        if (!$invoice->hasData()) {
            return null;
        }
        
        $order->setData('base_total_paid', 0);
        $order->setData('base_shipping_invoiced', 0);
        $order->setData('base_subtotal_invoiced', 0);
        $order->setData('shipping_invoiced', 0);
        $order->setData('total_invoiced', 0);
        $order->setData('total_paid', 0);
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllItems() as $item) {
            $item->setQtyInvoiced(0);
        }
        foreach ($invoice->getAllItems() as $item) {
            $item->delete();
        }
        $invoice->delete();
        $this->logger->info('step end');
    }
}
