<?php
namespace Indusa\Webservices\Model;
use Indusa\Webservices\Logger\Logger; 

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
	
    public function saveInventory($storeInventory = array(),$productSku = null, $serviceName = null)
    {
		if(!$storeInventory) return false;
		$wareHouseQty = 0;
		$data = array();
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$model = $objectManager->create('Indusa\Webservices\Model\InventoryStore');
		
		//Inventory Updates at real time
		if($serviceName == 'inventoryUpdates'){
			$inventoryStoreFactory  = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');			
			$resultFactory = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('ax_store_id', $storeInventory['storeId'])->addFieldToFilter('product_sku', $productSku)->setOrder('id', 'DESC')->getFirstItem();
			$storeRecID = $resultFactory->getId();		
	
				if($storeInventory['type'] != 'Warehouse')
				{
					if(isset($storeRecID)){
						$data = array('id'=> $storeRecID, 'ax_store_id' =>$storeInventory['storeId'],'product_sku'=>$productSku,'quantity'=>str_replace(',', '', $storeInventory['quantity'])); 
						
						$model->setData($data);
						$model->save();
					}
				}
				else
				{
				   $wareHouseQty =  str_replace(',', '', $storeInventory['quantity']);
				}
		}
		//Inventory data created while importing products
		else {	
								
			foreach($storeInventory as $_storeData)
			{
				if($_storeData['type'] != 'Warehouse')
				{
					$data = array('ax_store_id' =>$_storeData['storeID'],'product_sku'=>$productSku,'quantity'=>str_replace(',', '', $_storeData['qty']));
					$model->setData($data);
					$model->save();
				}
				else
				{
				   $wareHouseQty =  str_replace(',', '', $_storeData['qty']);
				}	
			}
		}		
        return $wareHouseQty;
    }   
}
?>