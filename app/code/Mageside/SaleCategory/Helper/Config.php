<?php
/**
 * Copyright © 2017 Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */

namespace Mageside\SaleCategory\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $registry;
    }
    
    /**
     * Get module settings
     *
     * @param $key
     * @param null $store
     * @return mixed
     */
    public function getConfigModule($key, $store = null)
    {
        return $this->scopeConfig->getValue(
            'mageside_salecategory/general/' . $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check category is it sale
     *
     * @return bool
     */
    public function isSaleCategory()
    {
        $category = $this->_coreRegistry->registry('current_category');
        if ($category
            && $category->getId() == $this->getConfigModule('category_id')
            && $this->getConfigModule('enabled')
        ) {
            return true;
        }
        return false;
    }
}
