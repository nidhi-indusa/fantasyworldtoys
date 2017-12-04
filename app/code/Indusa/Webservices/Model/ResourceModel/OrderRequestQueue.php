<?php
namespace Indusa\Webservices\Model\ResourceModel;

class OrderRequestQueue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('order_request_queue', 'id');
    }
}
?>