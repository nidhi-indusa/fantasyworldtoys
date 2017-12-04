<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Indusa\PriceDecimal\Block\Adminhtml\Product\Helper\Form;

/**
 * Product form price field helper
 */
class Price extends \Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Price
{
 
    /**
     * @param null|int|string $index
     * @return null|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getEscapedValue($index = null)
    {
        $value = $this->getValue();

        if (!is_numeric($value)) {
            return null;
        }

        if ($attribute = $this->getEntityAttribute()) {
            // honor the currency format of the store
            $store = $this->getStore($attribute);
            $currency = $this->_localeCurrency->getCurrency($store->getBaseCurrencyCode());
            $value = $currency->toCurrency($value, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);
        } else {
            // default format:  1234.56
            $value = number_format($value, 3, null, '');
        }

        return $value;
    }
}
