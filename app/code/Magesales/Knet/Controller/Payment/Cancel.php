<?php
	namespace Magesales\Knet\Controller\Payment;
	use Magesales\Knet\Controller\Main;
	use Magento\Framework\Controller\ResultFactory;
	
	class Cancel extends Main
	{
		public function execute()
		{
			$resultRedirect = $this->resultRedirectFactory->create();
			$session = $this->checkoutSession;
			$errorMsg = __(' There was an error occurred during paying process.');
			$orderIncrementId = '';
			
			if(array_key_exists('OrderID', $_GET))
			{
				$orderIncrementId = $_GET['OrderID'];
			}
			else
			{
				$orderIncrementId = $session->getLastRealOrderId();
			}
		    
			if ($orderIncrementId)
			{
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderIncrementId);
				
				//$orderPayment = $objectManager->create('Magento\Sales\Model\Order\Payment')->load($order->getId());
				
				//getting payment data
				$paymentCollection = $objectManager->create('Magento\Sales\Model\Order\Payment')->getCollection()->addFieldToSelect('entity_id')->addFieldToFilter("parent_id",$order->getEntityId())->getFirstItem();
				$orderPayment = $objectManager->create('Magento\Sales\Model\Order\Payment')->load($paymentCollection->getEntityId());
				
				$amount = round( $order->getGrandTotal(), 3 );
				
				if($order->getId() && array_key_exists('PaymentID', $_GET))
				{
					//Read URL params
					$paymentID = isset($_GET['PaymentID']) ? $_GET['PaymentID'] : '';
					$presult = isset($_GET['Result']) ? $_GET['Result'] : '';
					$postdate = isset($_GET['PostDate']) ? $_GET['PostDate'] : '';
					$tranid = isset($_GET['TranID']) ? $_GET['TranID'] : '';
					$auth = isset($_GET['Auth']) ? $_GET['Auth'] : '';
					$ref = isset($_GET['Ref']) ? $_GET['Ref'] : '';
					$trackid = isset($_GET['TrackID']) ? $_GET['TrackID'] : '';
					
					$message = 'KNET has declined the payment. <br/><br/>KNET Payment Details:<br/>';
					$message .= 'PaymentID: ' . $paymentID . "<br/>";
					$message .= 'Amount: ' . $amount . "<br/>";
					$message .= 'Result: ' . $presult . "<br/>";
					$message .= 'PostDate: ' . $postdate . "<br/>";
					$message .= 'TranID: ' . $tranid . "<br/>";
					$message .= 'Auth: ' . $auth . "<br/>";
					$message .= 'Ref: ' . $ref . "<br/>";
					$message .= 'TrackID: ' . $trackid . "<br/>";
					$message .= 'Time: ' . date('H:i:s') . "<br/>";
					
					//Add Order payment data in sales_order_payment table
					$orderPaymentTransactionData = json_encode($_GET);
					
					$orderPayment->setAdditionalData($orderPaymentTransactionData);
					$orderPayment->save();
					
					//Revert the quantity
					$orderItems = $order->getAllVisibleItems();
					foreach ($orderItems as $index => $item) {
						if($order->getDeliveryFrom() == "Store") {
							
							//final Store Quantity
							$inventoryStoreFactory = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
							
							$inventoryStoreModel = $objectManager->create('Indusa\Webservices\Model\InventoryStore');
							
							$resultFactory = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('ax_store_id', $order->getAxStoreId())->addFieldToFilter('product_sku', $item->getSku())->getFirstItem();
							
							$transferOrderQuantity = $item->getTransferOrderQuantity();
							$storeqty = intval($resultFactory->getQuantity());
							
							if($transferOrderQuantity > 0){
								
								$product = $objectManager->create('\Magento\Catalog\Model\Product')->loadByAttribute("sku",$item->getSku());
								$stockRegistry = $objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface');
								$stockItem = $stockRegistry->getStockItem($product->getEntityId());			
								
								$productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($product->getEntityId()); 
								
								//Restore warehouse qty 
								$restoreWarehouseQty = $productStockObj->getData('qty') + $transferOrderQuantity;
								
								//Final Warehouse Qty
								$finalWarehouseQty = $restoreWarehouseQty;
								
								if ($finalWarehouseQty > 0)
								$is_in_stock = 1;
								else
								$is_in_stock = 0;
								
								$stockItem->setData('qty', $finalWarehouseQty);
								$stockItem->setData('is_in_stock', $is_in_stock);
								$stockItem->setData('manage_stock', 1);
								$stockItem->setData('use_config_manage_stock', 1);											
								$stockRegistry->updateStockItemBySku($product->getData('sku'), $stockItem);
																
								//final Store Qty
								$finalStoreQty = $storeqty + ($item->getQtyOrdered() - $transferOrderQuantity);
								
								$data = array('id' => $resultFactory->getId(), 'ax_store_id' => $resultFactory->getAxStoreId(), 'product_sku' => $item->getSku(), 'quantity' => $finalStoreQty);
								$inventoryStoreModel->setData($data);									
								$inventoryStoreModel->save();
							}
							else{
							
								//final Store Qty
								$finalStoreQty = $storeqty + $item->getQtyOrdered();
								
								$data = array('id' => $resultFactory->getId(), 'ax_store_id' => $resultFactory->getAxStoreId(), 'product_sku' => $item->getSku(), 'quantity' => $finalStoreQty);
								$inventoryStoreModel->setData($data);									
								$inventoryStoreModel->save();
							}
							
							
						}
						else if($order->getDeliveryFrom() == "Warehouse"){
							$product = $objectManager->create('\Magento\Catalog\Model\Product')->loadByAttribute("sku",$item->getSku());
							$stockRegistry = $objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface');
							$stockItem = $stockRegistry->getStockItem($product->getEntityId());			
							
							$productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($product->getEntityId()); 
							
							//Restore warehouse qty 
							$restoreWarehouseQty = $productStockObj->getData('qty') + $item->getQtyOrdered();
							
							//Final Warehouse Qty
							$finalWarehouseQty = $restoreWarehouseQty;
							
							if ($finalWarehouseQty > 0)
							$is_in_stock = 1;
							else
							$is_in_stock = 0;
							
							$stockItem->setData('qty', $finalWarehouseQty);
							$stockItem->setData('is_in_stock', $is_in_stock);
							$stockItem->setData('manage_stock', 1);
							$stockItem->setData('use_config_manage_stock', 1);											
							$stockRegistry->updateStockItemBySku($product->getData('sku'), $stockItem);
							
							
						}						
					}					
					
					$order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
						$order->addStatusToHistory($order->getStatus(),$message);
					$order->save();
				}
				$this->_view->loadLayout();			
				$this->_view->renderLayout();
			}
			
		}
	}				