<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Manage;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class DeleteCreditCard extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \CyberSource\SecureAcceptance\Model\Token
     */
    private $token;

    /**
     * DeleteCreditCard constructor.
     * @param Context $context
     * @param \CyberSource\SecureAcceptance\Model\Token $token
     */
    public function __construct(
        Context $context,
        \CyberSource\SecureAcceptance\Model\Token $token
    ) {
        parent::__construct($context);
        $this->token = $token;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        try {
            $this->token->load($params['id'])->delete();
            $this->messageManager->addSuccess(__('Token has been deleted successfully'));
            $this->_redirect('cybersource/manage/card');
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
