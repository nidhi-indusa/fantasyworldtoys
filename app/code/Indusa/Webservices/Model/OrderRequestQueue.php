<?php
namespace Indusa\Webservices\Model;

class OrderRequestQueue extends \Magento\Framework\Model\AbstractModel
{
    /**
    * Initialize resource model
    *
    * @return void
    */
    protected function _construct()
    {
        $this->_init('Indusa\Webservices\Model\ResourceModel\OrderRequestQueue');
    }
    
    /**
    * @param info of order request queue  $QueueInfo
    * 
    * @return boolean
    */
    public function saveOrderRequestQueue($QueueInfo)
    {
        if(!$QueueInfo) return false;
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Indusa\Webservices\Model\OrderRequestQueue');
        $model->setData($QueueInfo);
		
        try {
            $model->save();
			$lastRequestId = $model->getId();
            return $lastRequestId;
		}
		catch (\Exception $e) {
            return false;
        }
    }
    
     /**
     * @param update process parameter  $updateProcess
     * 
     * @return boolean
     */
    public function updateOrderProcessQueue($updateProcess = null)
    {
        if(!$updateProcess) return false;
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Indusa\Webservices\Model\OrderRequestQueue');
        if($id = $updateProcess['id'])
        {
            $model->load($id);
            $model->setData($updateProcess);
        }
        try {
		    $model->save();
		    return true;
		}
		catch (\Exception $e) {
            return false;
        }         
    }
}
?>