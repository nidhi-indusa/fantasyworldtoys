<?php
namespace Indusa\Deliverymethod\Api\Quote\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Shipping DeliveryDate data interface
 *
 * @api
 */
interface ShippingInformationInterface extends \Magento\Framework\Api\CustomAttributesDataInterface
{

    const DELIVERY_DATE = 'delivery_date';
    /**
     * Retrieve DeliveryDate message
     *
     * @return string
     */
    public function getDeliveryDate();

    /**
     * Set DeliveryDate message
     *
     * @param string $delivery_date
     * @return $this
     */
    
    public function setDeliveryDate($delivery_date);
}