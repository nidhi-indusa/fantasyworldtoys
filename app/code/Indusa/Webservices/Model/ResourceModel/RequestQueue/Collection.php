<?php

namespace Indusa\Webservices\Model\ResourceModel\RequestQueue;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Indusa\Webservices\Model\RequestQueue', 'Indusa\Webservices\Model\ResourceModel\RequestQueue');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }

}
?>