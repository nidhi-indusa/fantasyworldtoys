<?php
namespace Indusa\Webservices\Model\ResourceModel;

class InventoryStore extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('catalog_product_store_inventory', 'id');
    }
}
?>