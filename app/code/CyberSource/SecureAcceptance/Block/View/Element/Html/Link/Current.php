<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Block\View\Element\Html\Link;

class Current extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * Current constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->_defaultPath = $defaultPath;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if ($this->moduleManager->isEnabled('CyberSource_SecureAcceptance') &&
            $this->_scopeConfig->getValue(
                "payment/chcybersource/active",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        ) {
            return parent::_toHtml();
        }
        return '';
    }
}
