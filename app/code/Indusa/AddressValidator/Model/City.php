<?php
namespace Indusa\AddressValidator\Model;

class City extends \Magento\Framework\Model\AbstractModel
{
    /**
    * Initialize resource model
    *
    * @return void
    */
    protected function _construct()
    {
        $this->_init('Indusa\AddressValidator\Model\ResourceModel\City');
    }
	
}
?>