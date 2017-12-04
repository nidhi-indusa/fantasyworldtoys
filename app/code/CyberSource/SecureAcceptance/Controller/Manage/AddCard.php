<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Manage;

use CyberSource\SecureAcceptance\Model\Token;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Result\PageFactory;

class AddCard extends \Magento\Framework\App\Action\Action
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
     * AddCard constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param SessionManagerInterface $customerSession
     * @param Token $token
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
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
            $page_object = $this->resultPageFactory->create();
            return $page_object;
        } else {
            return $this->_redirect('customer/account');
        }
    }

    public function _initToken($response, $incrementId, $customerId)
    {
        $tokenInfo = [
                'created_date' => gmdate("Y-m-d\\TH:i:s\\Z"),
                'customer_id' => $customerId,
                'order_id' => $incrementId,
                'quote_id' => isset($response['req_reference_number']) ? $response['req_reference_number'] : '',
                'payment_token' => isset($response['payment_token']) ? $response['payment_token'] : '',
                'customer_email' => isset($response['req_bill_to_email']) ? $response['req_bill_to_email'] : ''
            ];
        $this->token->setData($tokenInfo);
        try {
            $this->token->save();
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
