<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\Core\Block;

class Fingerprint extends \Magento\Framework\View\Element\Template
{

    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

   /**
    * Constructor
    *
    * @param \Magento\Framework\View\Element\Template\Context $context
    * @param array $data
    */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    public function getJsUrl()
    {
        return 'https://h.online-metrix.net/fp/tags.js?' . $this->composeUrlParams();
    }

    public function getIframeUrl()
    {
        return 'https://h.online-metrix.net/fp/tags?' . $this->composeUrlParams();
    }

    public function getOrgId()
    {
        $orgId = $this->_scopeConfig->getValue(
            "payment/chcybersource/org_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($orgId !== null || $orgId !== "") {
            return $orgId;
        }

        return null;
    }
    
    private function composeUrlParams()
    {
        $orgId = $this->getOrgId();
        $sessionId = $this->checkoutSession->getQuote()->getId().time();
        $merchantId = $this->_scopeConfig->getValue(
            "payment/chcybersource/merchant_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($this->_scopeConfig->getValue(
            "payment/chcybersource/fingerprint_enabled",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            $this->checkoutSession->setFingerprintId($sessionId);
            return 'org_id='.$orgId.'&session_id='.$merchantId.$sessionId;
        } else {
            $this->checkoutSession->setFingerprintId(null);
            return 'session_id='.$merchantId.$sessionId;
        }
    }
    
    public function isFingerprintEnabled()
    {
        return $this->_scopeConfig->getValue(
            "payment/chcybersource/fingerprint_enabled",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
