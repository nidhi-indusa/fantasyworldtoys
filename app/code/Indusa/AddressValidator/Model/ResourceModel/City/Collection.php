<?php

namespace Indusa\AddressValidator\Model\ResourceModel\RequestQueue;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Indusa\AddressValidator\Model\City', 'Indusa\AddressValidator\Model\ResourceModel\City');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }

}
?>