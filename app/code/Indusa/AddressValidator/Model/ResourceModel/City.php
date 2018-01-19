<?php
namespace Indusa\AddressValidator\Model\ResourceModel;

class City extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('city', 'id');
    }
}
?>