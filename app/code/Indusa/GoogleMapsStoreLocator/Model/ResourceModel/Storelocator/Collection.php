<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Indusa\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator;

use \Indusa\GoogleMapsStoreLocator\Model\ResourceModel\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'gmaps_id';
    protected $_previewFlag;
    protected function _construct()
    {
        $this->_init(
            
            'Indusa\GoogleMapsStoreLocator\Model\Storelocator',
            'Indusa\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator'
        );
        $this->_map['fields']['gmaps_id'] = 'main_table.gmaps_id';
    }
}
