<?php
namespace Indusa\Webservices\Model\ResourceModel;

class RequestQueue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('request_queue', 'id');
    }
}
?>