<?php
namespace Indusa\PriceDecimal\Model;



class Order extends \Magento\Sales\Model\Order
{
    
    /**
     * Get formatted price value including order currency rate to order website currency
     *
     * @param   float $price
     * @param   bool  $addBrackets
     * @return  string
     */
    public function formatPrice($price, $addBrackets = false)
    {
        return $this->formatPricePrecision($price, 3, $addBrackets);
    }

    
}
