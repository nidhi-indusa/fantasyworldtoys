<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CyberSource\PayPal\Controller\Express;

use Magento\Checkout\Helper\Data;
use Magento\Checkout\Helper\ExpressRedirect;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Exception;
use CyberSource\PayPal\Model\Express\Checkout;
use Magento\Framework\App\ObjectManager;
use CyberSource\PayPal\Model\Config as CyberSourcePayPalConfig;

/**
 * Class GetToken
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetToken extends AbstractExpress
{
    /**
     * Config mode type
     *
     * @var string
     */
    protected $_configType = \Magento\Paypal\Model\Config::class;

    /**
     * Config method type
     *
     * @var string
     */
    protected $_configMethod = \CyberSource\PayPal\Model\Config::CODE;

    /**
     * Checkout mode type
     *
     * @var string
     */
    protected $_checkoutType = \CyberSource\PayPal\Model\Express\Checkout::class;

    /**
     * @var \Psr\Log\LoggerInterface
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
     * @param \Psr\Log\LoggerInterface|null $logger
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
        \Psr\Log\LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?: ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
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
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $controllerResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $token = $this->getToken();
            if ($token === null) {
                $token = false;
            }
            $url = $this->_checkout->getRedirectUrl();
            $this->_initToken($token);
            $controllerResult->setData(['url' => $url]);
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception);
            $controllerResult->setData([
                'message' => [
                    'text' => $exception->getMessage(),
                    'type' => 'error'
                ]
            ]);
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('We can\'t start Express Checkout.')
            );

            return $this->getErrorResponse($controllerResult);
        }

        return $controllerResult;
    }

    /**
     * @return string|null
     * @throws LocalizedException
     */
    protected function getToken()
    {
        $this->_initCheckout();
        $quote = $this->_getQuote();
        $hasButton = $this->getRequest()->getParam(Checkout::PAYMENT_INFO_BUTTON) == 1;

        /** @var Data $checkoutHelper */
        $checkoutHelper = $this->_objectManager->get(Data::class);
        $quoteCheckoutMethod = $quote->getCheckoutMethod();
        $customerData = $this->_customerSession->getCustomerDataObject();

        if ($quote->getIsMultiShipping()) {
            $quote->setIsMultiShipping(false);
            $quote->removeAllAddresses();
        }

        if ($customerData->getId()) {
            $this->_checkout->setCustomerWithAddressChange(
                $customerData,
                $quote->getBillingAddress(),
                $quote->getShippingAddress()
            );
        } elseif ((!$quoteCheckoutMethod || $quoteCheckoutMethod !== Onepage::METHOD_REGISTER)
                && !$checkoutHelper->isAllowedGuestCheckout($quote, $quote->getStoreId())
        ) {
            $expressRedirect = $this->_objectManager->get(ExpressRedirect::class);

            $this->messageManager->addNoticeMessage(
                __('To check out, please sign in with your email address.')
            );

            $expressRedirect->redirectLogin($this);
            $this->_customerSession->setBeforeAuthUrl(
                $this->_url->getUrl('*/*/*', ['_current' => true])
            );

            return null;
        }

        return $this->_checkout->start(
            $this->_url->getUrl('*/*/return'),
            $this->_url->getUrl('*/*/cancel'),
            $hasButton
        );
    }

    /**
     * @param ResultInterface $controllerResult
     * @return ResultInterface
     */
    private function getErrorResponse(ResultInterface $controllerResult)
    {
        $controllerResult->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
        $controllerResult->setData(['message' => __('Sorry, but something went wrong')]);

        return $controllerResult;
    }
}
