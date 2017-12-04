<?php

namespace Indusa\GoogleMapsStoreLocator\Model\ResourceModel;

class Storelocator extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('indusa_googlemapsstorelocator', 'gmaps_id');
    }
}
