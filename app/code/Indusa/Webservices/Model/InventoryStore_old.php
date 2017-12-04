<?php
namespace Indusa\Webservices\Model;

class InventoryStore extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
	
    public function _construct() {
		$this->_init('Indusa\Webservices\Model\ResourceModel\InventoryStore');
    }
    
    /**
     * @param array of store inventory storeInventory
     * @param sku of product productSku 
     * 
     * @return warehouse product quantity
     */
	 
    public function saveInventory($storeInventory = array(),$productSku= null)
    {
		if(!$storeInventory) return false;
		$wareHouseQty = 0;
		$data = array();
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$model = $objectManager->create('Indusa\Webservices\Model\InventoryStore');
		$inventoryStoreFactory  = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
		 
		$resultFactory = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('ax_store_id', $storeInventory['storeId'])->addFieldToFilter('product_sku', $productSku)->getFirstItem();
		$storeRecID = $resultFactory->getId();		
		
		
		if(isset($storeRecID)){
			
			if($storeInventory['type'] != 'Warehouse')
            {
                $data = array('id'=> $storeRecID, 'ax_store_id' =>$storeInventory['storeId'],'product_sku'=>$productSku,'quantity'=>$storeInventory['quantity']); 
            }
            else
            {
               $wareHouseQty =  $storeInventory['quantity'];
            }
		}
		else{
			if($storeInventory['type'] != 'Warehouse')
			{
				$data = array('ax_store_id' =>$storeInventory['storeId'],'product_sku'=>$productSku,'quantity'=>$storeInventory['quantity']); 
			}
			else
			{
			   $wareHouseQty =  $storeInventory['quantity'];
			}
		}
			
        $model->setData($data);
        $model->save();
        return $wareHouseQty;
    }
   
}
?>