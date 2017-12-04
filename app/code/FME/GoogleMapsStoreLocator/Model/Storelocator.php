<?php

namespace FME\GoogleMapsStoreLocator\Model;

class Storelocator extends \Magento\Framework\Model\AbstractModel
{
        const STATUS_ENABLED = 1;
        const STATUS_DISABLED = 0;
        
    protected function _construct()
    {
        $this->_init('FME\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator');
    }
    public function getAvailableStatuses()
    {
        $availableOptions = ['1' => 'Enable',
                           '0' => 'Disable'];
        return $availableOptions;
    }
}
