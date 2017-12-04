<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use CyberSource\SecureAcceptance\Model\Token;
use Magento\Framework\Controller\Result\JsonFactory;

class GetTokens extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var JsonFactory $resultJsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    /**
     * Gettokens constructor.
     * @param Context $context
     * @param Session $session
     * @param Token $token
     */
    public function __construct(
        Context $context,
        Session $session,
        Token $token,
        JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->session = $session;
        $this->token = $token;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }
 
    public function execute()
    {
        $tokens = [];
        if ($this->session->isLoggedIn()) {
            $customerId = $this->session->getId();
            $tokenCollection = $this->token->getCollection();
            $tokenCollection->addFieldToFilter('customer_id', $customerId);
            $tokenCollection->addFieldToFilter('store_id', $this->_storeManager->getStore()->getId());
            $tokenCollection->setOrder('token_id', 'DESC');
            foreach ($tokenCollection as $token) {
                if ($token->getPaymentToken()) {
                    $tokens[$token->getPaymentToken()] = $token->getData('cc_last4');
                }
            }
        }

        $result = $this->resultJsonFactory->create();
        $result->setData($tokens);
        return $result;
    }
}
