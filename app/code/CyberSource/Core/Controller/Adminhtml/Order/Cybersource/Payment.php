<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\Core\Controller\Adminhtml\Order\Cybersource;

use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Escaper;
use Magento\Catalog\Helper\Product;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use CyberSource\Core\Helper\Data as DataHelper;
use Magento\Framework\Controller\ResultFactory;

class Payment extends \Magento\Sales\Controller\Adminhtml\Order\Create
{
    const ACTION_AUTHORIZE = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';
    
    /**
     * @var DataHelper
     */
    private $helper;
    
    /**
     *
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $magentoHelper;
    
    /**
     * @var \CyberSource\Core\Service\CyberSourceSoapAPI
     */
    private $api;
    
    /**
     * @var \CyberSource\Core\Model\Token
     */
    private $modelToken;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

   /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    private $scopeConfig;
    
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;
    
    /**
     * @var Session
     */
    private $checkoutSession;
    
    /**
     * Constructor
     *
     * @param Context $context
     * @param Product $productHelper
     * @param Escaper $escaper
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param DataHelper $helper
     */
    public function __construct(
        Context $context,
        Product $productHelper,
        Escaper $escaper,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        \CyberSource\Core\Service\CyberSourceSoapAPI $api,
        \CyberSource\Core\Model\Token $modelToken,
        DataHelper $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $magentoHelper,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->helper = $helper;
        $this->api = $api;
        $this->modelToken = $modelToken;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->magentoHelper = $magentoHelper;
        $this->quoteManagement = $quoteManagement;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $productHelper, $escaper, $resultPageFactory, $resultForwardFactory);
    }
    
    /**
     * Send request to cybersource
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $this->checkoutSession->setIsNotAdminCapture(true);
        $paymentParam = $this->getRequest()->getParam('payment');
        $this->logger->info("payment params = ".print_r($paymentParam, 1));
        $this->getRequest()->setPostValue('collect_shipping_rates', 1);
        $this->_processActionData('save');

        //get confirmation by email flag
        $orderData = $this->getRequest()->getPost('order');
        $sendConfirmationFlag = 0;
        if ($orderData) {
            $sendConfirmationFlag = !empty($orderData['send_confirmation']) ? 1 : 0;
        } else {
            $orderData = [];
        }

        $isCVVEnabled = (bool) $this->scopeConfig->getValue(
            "payment/chcybersource/enable_cvv",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $paymentAction = (bool) $this->scopeConfig->getValue(
            "payment/chcybersource/payment_action",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (
            (
                empty($paymentParam['token'])
            )
            || 
            (
                $isCVVEnabled
                && empty($paymentParam['cvv'])
            ) 
        ) {

            $this->messageManager->addErrorMessage(__('Please choose a payment method.'));
            $result['success'] = 0;

        } else {
            $tokenData = $this->modelToken->load($paymentParam['token'])->getData();

            if ($isCVVEnabled) {
                $tokenData['cvv'] = $paymentParam['cvv'];
            }

            if (isset($paymentParam['method']) && !empty($tokenData)) {
                $result = [];
                //create order partially
                $this->_getOrderCreateModel()->setPaymentData($paymentParam);
                $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentParam);

                $orderData['send_confirmation'] = $sendConfirmationFlag;
                $this->getRequest()->setPostValue('order', $orderData);

                try {
                    //do not cancel old order.
//                    $oldOrder = $this->_getOrderCreateModel()->getSession()->getOrder();
//                    $oldOrder->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_CANCEL, false);
                    
//                    $order = $this->_getOrderCreateModel()->setIsValidate(
//                        true
//                    )->importPostData(
//                        $this->getRequest()->getPost('order')
//                    )->createOrder();
                    $quote = $this->helper->getQuote();
                    $isCapture = ($paymentAction == self::ACTION_AUTHORIZE_CAPTURE) ? true : false;
                    $this->logger->info("before token request");
                    $response = $this->api->tokenPayment(
                        $tokenData,
                        false,
                        $isCapture,
                        $quote
                    );
                    $this->logger->info("after token request");
                    if ($response->reasonCode == 480 || $response->reasonCode == 100) {
                        $this->logger->info("before create order");
                        $order = $this->quoteManagement->submit($quote);
                        $this->logger->info("after create order");
                        $payment = $order->getPayment();
                        $payment->setCcTransId($response->requestID);
                        $payment->setLastTransId($response->requestID);
                        $payment->save();
                        $result['success'] = 1;
                    } else {
                        $result['success'] = 0;

                        $this->messageManager->addError("Some error during request to Cybersource. Code: "
                            . $response->reasonCode);
                    }

                    if ($response->reasonCode == 480) {
                        $order->setStatus($this->scopeConfig->getValue(
                            "payment/chcybersource/order_dm_status",
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        ));
                        $order->save();
                    }
                    $isError = false;
                } catch (\Exception $e) {
                    $this->messageManager->addExceptionMessage($e, __('Order saving error: %1', $e->getMessage()));
                    $isError = true;
                }

                if ($isError) {
                    $result['success'] = 0;
                    $result['error'] = 1;
                }

            } else {
                $this->messageManager->addErrorMessage(__('Please choose a payment method.'));
                $result['success'] = 0;
            }
        }
                
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        
        if (array_key_exists('success', $result) && $result['success']) {
            $resultRedirect->setPath('sales/order/index');
        } else {
            $resultRedirect->setPath('sales/order_create');
        }
        
        return $resultRedirect;
    }
}
