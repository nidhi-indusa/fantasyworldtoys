<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model\Payment;

use CyberSource\Core\Service\CyberSourceSoapAPI;
use Magento\Checkout\Model\Session;
use Magento\Paypal\Model\Express\Checkout as ExpressCheckout;
use Magento\Sales\Model\Order;

/**
 * Class Config
 */
class Bancontact extends \Magento\Payment\Model\Method\AbstractMethod
{
    
    const CODE = 'cybersource_bank_transfer_bancontact';
    
    protected $_code = self::CODE;
    protected $_isOffline = false;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canInvoice                  = true;
    protected $_canRefund                   = true;
    protected $_supportedCurrencyCodes = ['USD'];
    protected $_gatewayAPI = null;
    protected $_checkoutSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * Payment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param CyberSourceSoapAPI $cyberSourceAPI
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        CyberSourceSoapAPI $cyberSourceAPI,
        Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->storeManager = $storeManager;
        $this->_gatewayAPI = $cyberSourceAPI;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Capture payment abstract method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }
    
    /**
     * Refund payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_logger->info("refund bancontact");
        return $this;
    }
    
    /**
     * Is active
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return $this->isCurrencyAvailable() && (bool)(int)$this->_scopeConfig->getValue(
            "payment/cybersource_bank_transfer/bancontact_active",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    private function isCurrencyAvailable()
    {
        return (in_array(
            $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
            explode(',', $this->_scopeConfig->getValue(
                "payment/cybersource_bank_transfer/bancontact_currency",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ))
        ));
    }
}
