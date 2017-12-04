<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

use Magento\Framework\App\Action\Context;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Checkout\Model\Cart;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\QuoteManagement;

class LoadIFrame extends \Magento\Framework\App\Action\Action
{
    /**
     * @var RequestDataBuilder
     */
    private $helper;

    /**
     * Loadiframe constructor.
     * @param Context $context
     * @param RequestDataBuilder $helper
     */
    public function __construct(
        Context $context,
        RequestDataBuilder $helper,
        SessionManagerInterface $checkoutSession,
        SessionManagerInterface $customerSession,
        Cart $cart,
        LoggerInterface $logger,
        QuoteManagement $quoteManagement
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->cart = $cart;
        $this->logger = $logger;
        $this->quoteManagement = $quoteManagement;
        parent::__construct($context);
    }

    public function execute()
    {
        $guestEmail = $this->_request->getParam('quoteEmail');
        $paymentData = $this->helper->buildRequestData(true, $guestEmail);
        if (!preg_match('/embedded\/pay/', $paymentData['request_url'])) {
            $paymentData['request_url'] = str_replace('/pay', '/embedded/pay', $paymentData['request_url']);
        }
        $html = '<form id="cybersource-iframe-form" action="'.$paymentData['request_url'].'" method="post">';
        foreach ($paymentData as $name => $value) {
            $html .= '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
        }
        $html .= '</form>';

        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setContents($html);
        return $result;
    }
}
