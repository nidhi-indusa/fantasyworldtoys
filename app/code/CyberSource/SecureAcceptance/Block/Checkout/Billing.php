<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CyberSource\SecureAcceptance\Block\Checkout;

class Billing extends \Magento\Multishipping\Block\Checkout\Billing
{
    /**
     * @var \CyberSource\Core\Model\ResourceModel\Token\Collection
     */
    private $tokenCollection;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \CyberSource\SecureAcceptance\Model\Config
     */
    private $gatewayConfig;

    /**
     * @var \CyberSource\Core\Helper\RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * Billing constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory
     * @param \Magento\Multishipping\Model\Checkout\Type\Multishipping $multishipping
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Payment\Model\Method\SpecificationInterface $paymentSpecification
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \CyberSource\Core\Model\ResourceModel\Token\Collection $tokenCollection
     * @param \CyberSource\SecureAcceptance\Model\Config $gatewayConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory,
        \Magento\Multishipping\Model\Checkout\Type\Multishipping $multishipping,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Payment\Model\Method\SpecificationInterface $paymentSpecification,
        \Magento\Customer\Model\Session $customerSession,
        \CyberSource\Core\Model\ResourceModel\Token\Collection $tokenCollection,
        \CyberSource\SecureAcceptance\Model\Config $gatewayConfig,
        \CyberSource\Core\Helper\RequestDataBuilder $requestDataBuilder,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $paymentHelper,
            $methodSpecificationFactory,
            $multishipping,
            $checkoutSession,
            $paymentSpecification,
            $data
        );

        $this->tokenCollection = $tokenCollection;
        $this->customerSession = $customerSession;
        $this->gatewayConfig = $gatewayConfig;
        $this->requestDataBuilder = $requestDataBuilder;
    }

    public function isSilentPost()
    {
        return $this->gatewayConfig->isSilent();
    }

    /**
     * @return array
     */
    public function getVaults()
    {
        $data = [];
        if ($this->customerSession->isLoggedIn()) {
            $this->tokenCollection->addFieldToFilter('customer_id', $this->customerSession->getCustomer()->getId());
            $this->tokenCollection->load();
            if ($this->tokenCollection->getSize() > 0) {
                foreach ($this->tokenCollection as $token) {
                    $data[] = ['id' => $token->getId(), 'title' => $token->getData('cc_last4')];
                }
            }
        }
        return $data;
    }

    public function getSignedUrl()
    {
        return $this->_urlBuilder->getUrl('cybersource/manage/getsignedfields');
    }

    /**
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->_urlBuilder->getUrl('cybersource/manage/createcard');
    }

    public function getCcTypes()
    {
        return $this->requestDataBuilder->getCcTypes();
    }
}
