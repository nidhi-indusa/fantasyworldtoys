<?php
namespace Indusa\Webservices\Model;

class RequestQueue extends \Magento\Framework\Model\AbstractModel
{
    /**
    * Initialize resource model
    *
    * @return void
    */
    protected function _construct()
    {
        $this->_init('Indusa\Webservices\Model\ResourceModel\RequestQueue');
    }
    
    /**
    * @param info of request queue  $QueueInfo
    * 
    * @return boolean
    */
    public function saveRequestQueue($QueueInfo)
    {
        if(!$QueueInfo) return false;
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
        $model->setData($QueueInfo);
		
        try {
            $model->save();
            return true;
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
    public function updateProcessQueue($updateProcess = null)
    {
        if(!$updateProcess) return false;
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
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