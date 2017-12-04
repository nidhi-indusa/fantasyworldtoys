<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

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
        \Magento\Framework\App\Action\Context $context,
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
        $url = $this->_url->getUrl('checkout');

        if (!$this->scopeConfig->getValue(
            "payment/chcybersource/use_iframe",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            return $this->_redirect('checkout');
        }
        $html = '<html>
                    <body>
                        <script type="text/javascript">
                            window.onload = function() {
                                parent.window.location = "'.$url.'";
                            };
                        </script>
                    </body>
                </html>';
        /**
         * @var $result \Magento\Framework\Controller\ResultInterface
         */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setContents($html);
        return $result;
    }
}
