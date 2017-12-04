<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Manage;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Model\QuoteManagement;
use CyberSource\SecureAcceptance\Model\Token;
use Magento\Checkout\Model\Cart;

class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    
    /**
     * Cancel constructor.
     * @param Context $context
     * @param Session $session
     * @param QuoteManagement $quoteManagement
     * @param Token $token
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        Session $session,
        QuoteManagement $quoteManagement,
        Token $token,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Cart $cart
    ) {
        $this->session = $session;
        $this->quoteManagement = $quoteManagement;
        $this->token = $token;
        $this->cart = $cart;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        $url = $this->_url->getUrl('cybersource/manage/card');
        $this->messageManager->addSuccess(__('You have canceled successful.'));
        
        if (!$this->scopeConfig->getValue(
            "payment/chcybersource/use_iframe",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            return $this->_redirect('cybersource/manage/card');
        }
        $html = '<html>
                    <body>
                        <script type="text/javascript">
                            window.onload = function() {
                                window.top.location.href = "'.$url.'";
                            };
                        </script>
                    </body>
                </html>';
        $result = $this->resultFactory->create('raw');
        $result->setContents($html);
        return $result;
    }
}
