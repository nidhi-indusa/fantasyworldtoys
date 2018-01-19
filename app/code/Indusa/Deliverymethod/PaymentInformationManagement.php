<?php

/**
	* Indusa Deliverymethod
	*
	* @category     Indusa_Deliverymethod
	* @package      Indusa_Deliverymethod
	* @author      Indusa_Deliverymethod Team
	* @copyright    Copyright (c) 2017 Indusa Deliverymethod (http://www.indusa.com/)
	* @license      http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

namespace Indusa\Deliverymethod\Model;

use Magento\Framework\Exception\CouldNotSaveException;

class PaymentInformationManagement extends \Magento\Checkout\Model\PaymentInformationManagement {
	
	public function savePaymentInformationAndPlaceOrder(
	$cartId, \Magento\Quote\Api\Data\PaymentInterface $paymentMethod, \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
	) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$quote = $this->quoteRepository->getActive($cartId);
		
		//Check for delivery_method seleceted clickandcollect and delivery_from store start
		if ($quote->getDeliveryMethod() == 'clickandcollect' && $quote->getDeliveryFrom() == 'Store') {
			
			
			$quote = $this->quoteRepository->get($quote->getEntityId());
			$items = $quote->getAllItems();
			
			foreach ($items as $index => $item) {
				
				$quote = $objectManager->create('\Magento\Quote\Model\Quote')->load($item->getQuoteId());
				$requestedQty = $item->getQty();
				$productId = $item->getProductId();
				$product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);
				$productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($productId);
				$ax_storeid = $quote->getAxStoreId();
				$inventoryStoreFactory = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
				$resultFactory = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('ax_store_id', $ax_storeid)->addFieldToFilter('product_sku', $product->getData('sku'))->getFirstItem();
				if ($resultFactory->getId() > 0) {
					
					$storeqty = intval($resultFactory->getQuantity());
					$requestedQty = intval($item->getQty());
					$response = $this->checkStoreAvaibilty($productStockObj->getData('qty'), $storeqty, $requestedQty);
					} else {
					$storeqty = 0;
					$response = $this->checkStoreAvaibilty($productStockObj->getData('qty'), $storeqty, $requestedQty);
				}
			}
			
			if ($response == 0) {
				
				throw new CouldNotSaveException(__('Unable to proceed further due to Selected Click and Collect Method stock not available.'));
				return false;
				} else {
				$this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
				try {
					$orderId = $this->cartManagement->placeOrder($cartId);					
					
					$orderData = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);
					$orderItems = $orderData->getAllVisibleItems();
					foreach ($orderItems as $index => $item) {
						$quote = $objectManager->create('\Magento\Quote\Model\Quote\Item')->load($item->getQuoteItemId());
						
						$requestedQty = $quote->getQty();
						
						$productId = $quote->getProductId();
						$product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);
						$productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($productId);
						$ax_storeid = $quote->getAxStoreId();
						$inventoryStoreFactory = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
						
						$inventoryStoreModel = $objectManager->create('Indusa\Webservices\Model\InventoryStore');
						
						$resultFactory = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('ax_store_id', $ax_storeid)->addFieldToFilter('product_sku', $item->getSku())->getFirstItem();
						
						if ($resultFactory->getId() > 0) {
							if ($response == 1) {
								$stockRegistry = $objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface');
								$stockItem = $stockRegistry->getStockItem($productId);
								
								$storeqty = intval($resultFactory->getQuantity());
								
								
								if ($storeqty >= $requestedQty) {
									
										$productRepository = $objectManager->get('Magento\Catalog\Api\ProductRepositoryInterface');
										$product = $productRepository->get($item->getSku());        
										$productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($product->getId());  
										
										//Restore warehouse qty 
										$restoreWarehouseQty = $productStockObj->getData('qty')+$requestedQty;
										
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
									
									//final Store Quantity
									$finalStoreQty = $storeqty - $requestedQty;
									
									$data = array('id' => $resultFactory->getId(), 'ax_store_id' => $resultFactory->getAxStoreId(), 'product_sku' => $item->getSku(), 'quantity' => $finalStoreQty);
										$inventoryStoreModel->setData($data);									
										$inventoryStoreModel->save();
								}
								else {
									
										$productRepository = $objectManager->get('\Magento\Catalog\Model\ProductRepository');
										$product = $productRepository->get($item->getSku());        
										$productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($product->getId()); 
										
										//Restore warehouse qty 
										$restoreWarehouseQty = $productStockObj->getData('qty')+$requestedQty;
										
										//Final Warehouse Qty
																				
										$differenceQty = $requestedQty-$resultFactory->getQuantity();
										$finalWarehouseQty = $restoreWarehouseQty - $differenceQty;
										
										if ($finalWarehouseQty > 0)
										$is_in_stock = 1;
										else
										$is_in_stock = 0;
										
										$stockItem->setData('qty', $finalWarehouseQty);
										$stockItem->setData('is_in_stock', $is_in_stock);
										$stockItem->setData('manage_stock', 1);
										$stockItem->setData('use_config_manage_stock', 1);
										$stockRegistry->updateStockItemBySku($product->getData('sku'), $stockItem);
										
										//final Store Quantity
									$finalStoreQty = 0;
									
									$data = array('id' => $resultFactory->getId(), 'ax_store_id' => $resultFactory->getAxStoreId(), 'product_sku' => $item->getSku(), 'quantity' => $finalStoreQty);
										$inventoryStoreModel->setData($data);									
										$inventoryStoreModel->save();
								}								
							}
						}
					}
					} catch (\Exception $e) {
					throw new CouldNotSaveException(
					__('An error occurred on the server. Please try to place the order again.'), $e
					);
				}
				
				return $orderId;
			}
			} else if ($quote->getDeliveryMethod() == 'homedelivery' && $quote->getDeliveryFrom() == 'Warehouse') {
			
			$deliveryDate = $quote->getDeliveryDate();
			if ($deliveryDate != '') {
				$canBeDelivered = 0;
				$canBeDelivered = $this->getCheckDeliveryDateStatus($deliveryDate);
			}
			
			if (!$canBeDelivered) {
				
				$msg = "Sorry, your order cannot be shipped on date chosen. Please do choose another date !";
				throw new CouldNotSaveException(__($msg));
			}
			$this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
			
			try {
				
				$orderId = $this->cartManagement->placeOrder($cartId);
				
				} catch (\Exception $e) {
				throw new CouldNotSaveException(
				__('An error occurred on the server. Please try to place the order again.'), $e
				);
			}
			return $orderId;
			} else {		
			
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
	
	public function checkStoreAvaibilty($productstockqty, $storeqty, $requestedQty) {
		
		if ($storeqty > $requestedQty) {
			$totqty = $storeqty;
			} else {
			$totqty = $storeqty + $productstockqty;
		}
		if ($totqty >= $requestedQty) {
			return 1;
			} else {
			return 0;
		}
	}
	
	public function getStockItem($productId) {
		return $this->_stockItemRepository->get($productId);
	}
	
	public function getCheckDeliveryDateStatus($date) {
		
		static $canBeDelivered = 0;
		static $countOfOrders = 0;
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$maxorders = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('indusa_deliverydatemethod/general/maxorders');
		
		//countOfOrders = check in WEB DB about all Orders where deliveryDate= DELIVERYDATE AND STATUS != COMPLETED && DELIVERYTYPE = HOMEDELIVERY
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$allorder = $objectManager->create('\Magento\Sales\Model\Order')->getCollection()
		->addFieldToFilter('status', array('neq' => 'complete'))
		->addFieldToFilter('delivery_method', 'homedelivery')
		->addFieldToFilter('delivery_date', array('eq' => $date));
		
		$countOfOrders = count($allorder);
		
		if ($countOfOrders < $maxorders) {
			$canBeDelivered = 1;
			
			} else {
			$canBeDelivered = 0;
			
		}
		return $canBeDelivered;
	}
	
	public function CheckBestDeliveryDate($date) {
		
		$status = false;
		$i = 0;
		while ($status === false) {
			
			$maxorders = 1; 
			//countOfOrders = check in WEB DB about all Orders where deliveryDate= DELIVERYDATE AND STATUS != COMPLETED && DELIVERYTYPE = HOMEDELIVERY
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$allorder = $objectManager->create('\Magento\Sales\Model\Order')->getCollection()
			//->addFieldToFilter('status', 'pending')
			->addFieldToFilter('status', array('neq' => 'complete'))
			->addFieldToFilter('delivery_method', 'homedelivery')
			->addFieldToFilter('delivery_date', array('eq' => $date));
			$countOfOrders = count($allorder);
			
			if ($countOfOrders < $maxorders) {
				$canBeDelivered = 1;
				$status = true;
				$this->_configWriter->save('indusa_deliverydatemethod/general/show_hide_canBeDelivered', 1, 'default', 0);
				} else {
				$i++;
				$date = date('Y-m-d', strtotime($date . ' +' . $i . ' day'));
				$status = false;
				$this->_configWriter->save('indusa_deliverydatemethod/general/show_hide_canBeDelivered', 0, 'default', 0);
				return $this->CheckBestDeliveryDate($date);
			}
		}
		return $date;
	}
	
}
