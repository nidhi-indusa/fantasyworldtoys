<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Manage;

use Magento\Framework\App\Action\Context;
use CyberSource\SecureAcceptance\Model\Token;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Result\PageFactory;

class AddCreditCard extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * AddCreditCard constructor.
     * @param Context $context
     * @param SessionManagerInterface $customerSession
     * @param Token $token
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        SessionManagerInterface $customerSession,
        Token $token,
        PageFactory $resultPageFactory
    ) {
        $this->customerSession = $customerSession;
        $this->token = $token;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }
    
    public function execute()
    {
        if ($this->customerSession->getId()) {
            return $this->resultPageFactory->create();
        } else {
            return $this->_redirect('customer/account');
        }
    }
}
