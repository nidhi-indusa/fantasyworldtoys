<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

use CyberSource\SecureAcceptance\Model\Token;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Context;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use CyberSource\Core\Service\CyberSourceSoapAPI;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\QuoteManagement;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\JsonFactory;

class LoadInfo extends \Magento\Framework\App\Action\Action
{
    /**
     * @var RequestDataBuilder
     */
    private $onSiteDataHelper;

    /**
     * @var CyberSourceSoapAPI
     */
    private $cyberSourceAPI;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var JsonFactory $resultJsonFactory
     */
    private $resultJsonFactory;

    /**
     * LoadInfo constructor.
     * @param Context $context
     * @param RequestDataBuilder $helper
     * @param CyberSourceSoapAPI $cyberSourceApi
     * @param Token $token
     * @param SessionManagerInterface $checkoutSession
     * @param SessionManagerInterface $customerSession
     * @param Cart $cart
     * @param LoggerInterface $logger
     * @param QuoteManagement $quoteManagement
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        RequestDataBuilder $helper,
        CyberSourceSoapAPI $cyberSourceApi,
        Token $token,
        SessionManagerInterface $checkoutSession,
        SessionManagerInterface $customerSession,
        Cart $cart,
        LoggerInterface $logger,
        QuoteManagement $quoteManagement,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        JsonFactory $resultJsonFactory
    ) {
        $this->onSiteDataHelper = $helper;
        $this->cyberSourceAPI = $cyberSourceApi;
        $this->token = $token;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->cart = $cart;
        $this->logger = $logger;
        $this->quoteManagement = $quoteManagement;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $guestEmail = $this->_request->getParam('quoteEmail');
        $checkIframe = $this->_request->getParam('checkIframe', 0);
        if ($checkIframe && $this->onSiteDataHelper->getUseIframe()) {
            $paymentData = ['use_iframe' => 1];
        } else {
            $paymentData = $this->onSiteDataHelper->buildRequestData(false, $guestEmail);
        }

        $result = $this->resultJsonFactory->create();
        $result->setData($paymentData);
        return $result;
    }

    public function placeOrder($response)
    {
        $url = $this->_url->getUrl();
        if (isset($response->decision) && $response->decision == 'DECLINE') {
            $this->messageManager->addErrorMessage(__('Sorry but your transaction was unsuccessful.'));
            $url = $this->_url->getUrl('checkout/cart');
        }

        if ('ERROR' === $response->decision || (100 != $response->reasonCode && $response->reasonCode != 480)) {
            $this->checkoutSession->resetCheckout();
            $this->messageManager->addErrorMessage(__($response['message']));
            $url = $this->_url->getUrl('checkout/cart');
        }

        if (100 == $response->reasonCode || $response->reasonCode == 480) {
            $quote = $this->checkoutSession->getQuote();
            if ($response->merchantReferenceCode == $quote->getId()) {
                try {
                    if (!$this->customerSession->isLoggedIn()) {
                        $quote->setCustomerIsGuest(1);
                        $quote->setCheckoutMethod('guest');
                        $quote->setCustomerId(null);
                        $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
                        $quote->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                    }

                    $quote->setPaymentMethod('cybersource');
                    $quote->setInventoryProcessed(false);
                    $quote->save();
                    $quote->getPayment()->importData([
                        'method' => 'cybersource',
                        'last_trans_id' => $response->requestID,
                        'cc_trans_id' => $response->requestID
                    ]);

                    // Collect Totals & Save Quote
                    $quote->collectTotals()->save();
                    $this->checkoutSession->setIsRequestAuthorizeType(true);

                    // Create Order From Quote
                    $order = $this->quoteManagement->submit($quote);
                    $payment = $order->getPayment();
                    $payment->setCcTransId($response->requestID);
                    $payment->setLastTransId($response->requestID);
                    $payment->setAdditionalData($response->requestToken);
                    $payment->save();
                    if ($this->checkoutSession->getIsRequestAuthorizeType()) {
                        if ($response->reasonCode == 480) {
                            $order->setStatus(
                                $this->scopeConfig->getValue(
                                    "payment/chcybersource/order_dm_status",
                                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                                )
                            );
                        } else {
                            $order->setStatus(Order::STATE_PENDING_PAYMENT);
                        }
                        $order->setState(Order::STATE_PENDING_PAYMENT);
                        $order->save();
                        $this->checkoutSession->setIsRequestAuthorizeType(null);
                    } elseif ($response->reasonCode == 480) {
                        $order->setStatus(
                            $this->scopeConfig->getValue(
                                "payment/chcybersource/order_dm_status",
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                            )
                        );
                        $order->setState(Order::STATE_PENDING_PAYMENT);
                        $order->save();
                    }
                    $this->logger->info(
                        "Status = " . $this->scopeConfig->getValue(
                            "payment/chcybersource/order_dm_status",
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        )
                    );
                    $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
                    $this->checkoutSession->setLastQuoteId($quote->getId());
                    $this->checkoutSession->setLastOrderId($order->getId());
                    $this->checkoutSession->setLastOrderStatus($order->getStatus());
                    $this->checkoutSession->setLastRealOrderId($order->getRealOrderId());
                    $this->cart->truncate()->save();
                    $url = $this->_url->getUrl('checkout/onepage/success');
                    $this->messageManager->addSuccessMessage(__('Your order has been successfully created!'));
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        $jsonResponse = [
            'status' => 200,
            'redirect_url' => $url
        ];

        $result = $this->resultJsonFactory->create();
        $result->setData($jsonResponse);
        return $result;
    }
}
