<?php
namespace  MageVision\Blog16\Model;
 
use Magento\Framework\Exception\CouldNotSaveException;
use Indusa\Webservices\Model\InventoryStoreFactory;

class PaymentInformationManagement extends \Magento\Checkout\Model\PaymentInformationManagement{
   
    
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $quote = $this->quoteRepository->getActive($cartId);
            
        //echo "<pre>";
        //print_r($quote->getDeliveryMethod());
       
        
        
        //Check for delivery_method seleceted clickandcollect and delivery_from store start
        if($quote->getDeliveryMethod() == 'clickandcollect'  && $quote->getDeliveryFrom() == 'Store' ){
            
            //$response =  $this->CheckQtyAvaibility($quoteitemdata,$storeinventorydata,$productid);
            //$response =  0;
            $quote = $this->quoteRepository->get($quote->getEntityId());
            $items = $quote->getAllItems();
            foreach ($items as $index => $item) {
                $quote = $objectManager->create('\Magento\Quote\Model\Quote')->load($item->getQuoteId());
                $requestedQty =  $item->getQty();
                $productId = $item->getProductId();
                $product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);
                $productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($productId);
                $ax_storeid = $quote->getAxStoreId(); 
                $inventoryStoreFactory  = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
                $resultFactory = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('ax_store_id', $ax_storeid)->addFieldToFilter('product_sku', $product->getData('sku'))->getFirstItem();
                if($resultFactory->getId() > 0){
                
                 //   echo "prodcut stockqty::".$productStockObj->getData('qty');echo  "<br>";
                 //   echo "store float qty::".number_format($resultFactory->getQuantity(),4);echo  "<br>";
                    
                    
                    $storeqty = number_format($resultFactory->getQuantity(),4);
                    $requestedQty = number_format($item->getQty(),4);
                   // echo "requestedQty::".$requestedQty;echo  "<br>";
                    
                    $response = $this->checkStoreAvaibilty($productStockObj->getData('qty'),$storeqty,$requestedQty); 
                    
                   
                    
                   // echo  "response::".$response;
                   //  die();
                }else{
                    $response = 0;
                }
                
             }
//             echo  $response;echo  "<br>";
//             die();
            if($response==0)
           {
                // echo "response if part::".$response;die();
              throw new CouldNotSaveException(__('Unable to proceed further due to Selected Click and Collect Method stock not available...  '));
              return false;
 
           }else{
               //echo "response else part::".$response;die();
           $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
           
                
           
           try {
               $orderId = $this->cartManagement->placeOrder($cartId);
                if($response == 1){
                        $stockRegistry = $objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface');
                        $stockItem = $stockRegistry->getStockItem($productId);
                        if($storeqty <= $requestedQty){
                            $newqty = $productStockObj->getData('qty') + $storeqty;
                            $stockItem->setData('qty',$newqty);
                            $stockRegistry->updateStockItemBySku($product->getData('sku'), $stockItem);
                        }
                    }
               
                
           } catch (\Exception $e) {
                throw new CouldNotSaveException(
                __('An error occurred on the server. Please try to place the order again.'), $e
                );
           }
            return $orderId;
           }
            
          
            
            
        }else if($quote->getDeliveryMethod() == 'homedelivery'  && $quote->getDeliveryFrom() == 'Warehouse' ){
          //  echo "else if part homedelivery...";die();
            $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
             try {
                 
                  $orderId = $this->cartManagement->placeOrder($cartId);
             } catch (\Exception $e) {
                  throw new CouldNotSaveException(
                  __('An error occurred on the server. Please try to place the order again.'), $e
                  );
             }
              return $orderId;
            
            
        }else{
            
            // echo "response else part...";die();
            
             $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
           try {
                $orderId = $this->cartManagement->placeOrder($cartId);
           } catch (\Exception $e) {
                throw new CouldNotSaveException(
                __('An error occurred on the server. Please try to place the order again.'), $e
                );
           }
            return $orderId;
            
        }
        //Check for delivery_method seleceted clickandcollect and delivery_from store end
             
    
        
 
        
    }
    
     public function checkStoreAvaibilty($productstockqty,$storeqty,$requestedQty) {
       
//         echo "productstockqty::".$productstockqty;echo  "<br>";
//         echo "storeqty::".$storeqty;echo  "<br>";
//         echo "requestedQty::".$requestedQty;echo  "<br>";
         if($storeqty >= $requestedQty)
         {
           $totqty = $storeqty;
         }
         else{
         $totqty = $storeqty + $productstockqty;
         }
         if($totqty >= $requestedQty ){
             return 1;
         }else{
             return 0;
         }
       
    }
    
    
    
     public function getStockItem($productId) {
        return $this->_stockItemRepository->get($productId);
    }
    
}