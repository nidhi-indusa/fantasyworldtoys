<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Manage;

use Magento\Framework\App\Action\Context;
use CyberSource\SecureAcceptance\Helper\TokenRequestDataBuilder;
use Magento\Framework\Controller\ResultFactory;

class CreateCard extends \Magento\Framework\App\Action\Action
{
    /**
     * @var TokenRequestDataBuilder
     */
    private $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * Createcard constructor.
     * @param Context $context
     * @param TokenRequestDataBuilder $helper
     */
    public function __construct(
        Context $context,
        TokenRequestDataBuilder $helper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $cardAddress = $this->getRequest()->getParams();

        if (!empty($cardAddress['multishipping'])) {
            $this->checkoutSession->setIsMultiShipping(1);
        }

        $data = $this->helper->buildTokenData($cardAddress, true);

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $resultRedirect->setContents($data);
        return $resultRedirect;
    }
}
