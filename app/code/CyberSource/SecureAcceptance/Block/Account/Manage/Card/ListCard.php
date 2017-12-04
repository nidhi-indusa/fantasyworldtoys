<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Block\Account\Manage\Card;

use CyberSource\SecureAcceptance\Model\Token;

/**
 * ListCard
 */
class ListCard extends \Magento\Framework\View\Element\Template
{
    /**
     * @var mixed
     */
    private $curPage;

    /**
     * @var Token
     */
    private $token;
    
    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    private $customer;
    
    /**
     *
     * @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder
     */
    private $helper;
    
    /**
     * Listcard constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Token $token
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $dataBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Token $token,
        \Magento\Customer\Model\Session $customerSession,
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $dataBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->token = $token;
        $this->pageConfig->getTitle()->set(__('My Card'));
        $this->curPage = $this->getRequest()->getParam('p');
        $this->customer = $customerSession;
        $this->helper = $dataBuilder;
    }

    public function getTokens()
    {
        $collection = $this->token->getCollection();
        $collection->addFieldToFilter(
            'customer_id',
            $this->customer->getCustomerId()
        );
        $collection->addFieldToFilter('store_id', $this->_storeManager->getStore()->getId());
        $collection->setOrder('created_date', 'desc');
        $collection->setPageSize(10);
        if ($this->curPage) {
            $collection->setCurPage($this->curPage);
        }
        $collection->load();
        return $collection;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getTokens()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'shcybersource.manage.card.pager'
            )->setCollection(
                $this->getTokens()
            )->setShowAmounts(true);
            $this->setChild('pager', $pager);
            $this->getTokens()->load();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @param string $cardCode
     * @return string
     */
    public function getCardName($cardCode)
    {
        return $this->helper->getCardName($cardCode);
    }
}
