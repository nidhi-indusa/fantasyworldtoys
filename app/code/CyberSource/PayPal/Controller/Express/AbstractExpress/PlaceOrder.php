<?php

namespace CyberSource\PayPal\Controller\Express\AbstractExpress;

use CyberSource\PayPal\Model\Config as CyberSourcePayPalConfig;
use Psr\Log\LoggerInterface;

/**
 * Class PlaceOrder
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrder extends \CyberSource\PayPal\Controller\Express\AbstractExpress
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \CyberSource\PayPal\Model\Express\Checkout\Factory $checkoutFactory
     * @param \Magento\Framework\Session\Generic $paypalSession
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \CyberSource\PayPal\Model\Express\Checkout\Factory $checkoutFactory,
        \Magento\Framework\Session\Generic $paypalSession,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Customer\Model\Url $customerUrl,
        CyberSourcePayPalConfig $gatewayConfig,
        \Psr\Log\LoggerInterface $logger
    ) {

        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $orderFactory,
            $checkoutFactory,
            $paypalSession,
            $urlHelper,
            $customerUrl,
            $gatewayConfig
        );

        $this->logger = $logger;
    }

    /**
     * Submit the order
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            $this->_initCheckout();
            $this->_checkout->place($this->_initToken());

            // prepare session to success or cancellation page
            $this->_getCheckoutSession()->clearHelperData();

            // "last successful quote"
            $quoteId = $this->_getQuote()->getId();
            $this->_getCheckoutSession()->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);

            // an order may be created
            $order = $this->_checkout->getOrder();
            if ($order) {
                $this->_getCheckoutSession()->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
            }

            $this->_initToken(false);
            $this->_redirect('checkout/onepage/success');
            return;
        } catch (\Exception $e) {
            $this->logger->critical("Unable to place PayPal Order: " . $e->getMessage());
            $this->_redirectToCartAndShowError(__('We can\'t place the order.'));
        }
    }

    /**
     * Redirect customer back to PayPal with the same token
     *
     * @return void
     */
    protected function _redirectSameToken()
    {
        $token = $this->_initToken();
        $this->getResponse()->setRedirect(
            $this->gatewayConfig->getExpressCheckoutStartUrl($token)
        );
    }

    /**
     * Redirect customer to shopping cart and show error message
     *
     * @param string $errorMessage
     * @return void
     */
    protected function _redirectToCartAndShowError($errorMessage)
    {
        $this->messageManager->addErrorMessage($errorMessage);
        $this->_redirect('checkout/cart');
    }
}
