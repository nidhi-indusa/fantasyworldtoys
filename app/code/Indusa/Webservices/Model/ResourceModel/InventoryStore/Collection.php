<?php

namespace Indusa\Webservices\Model\ResourceModel\InventoryStore;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Indusa\Webservices\Model\InventoryStore', 'Indusa\Webservices\Model\ResourceModel\InventoryStore');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }

}
?>