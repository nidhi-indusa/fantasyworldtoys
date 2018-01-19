<?php
	/**
		* Indusa Webservice handling product add/update
		* Copyright (C) 2017 Indusa
		* 
		* This file included in Indusa/Webservices is licensed under OSL 3.0
		* 
		* http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
		* Please see LICENSE.txt for the full text of the OSL 3.0 license
	*/
	
	namespace Indusa\Webservices\Model;
	use Indusa\Webservices\Model\Service;
	use Magento\Sales\Model\Order;
	use Magento\Framework\Event\ObserverInterface;
	use Indusa\Webservices\Logger\Logger;
	
	class ManageApiManagement
	{    
		public $_availableService = array();		
		public $authorization;
		
		protected $date;		
		protected $logger;
		protected $_orderRequestQueueFactory;
		protected $resultPageFactory;
		protected $searchCriteriaBuilder;
		protected $collectionFactory;
		
		public function __construct(\Magento\Framework\Stdlib\DateTime\DateTime $date , \Indusa\Webservices\Logger\Logger $loggerInterface,	\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,\Magento\Sales\Api\OrderRepositoryInterface $orderRepository, \Indusa\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator\CollectionFactory $collectionFactory,		
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Indusa\Webservices\Model\OrderRequestQueueFactory $orderRequestQueueFactory)
		{
			$this->date = $date;
			$this->logger = $loggerInterface;
			$this->_availableService = array(Service::MANAGE_PRODUCTS,Service::INVENTORY_UPDATES,Service::PRICE_UPDATES,Service::RELATED_PRODUCTS,Service::CREATE_ORDER_AND_CUSTOMER,Service::ORDER_STATUS_UPDATES,Service::SEND_ACKNOWLEDGEMENT_TO_MAGENTO,Service::PRODUCT_STATUS_UPDATES,Service::RESERVED_INVENTORY_UPDATES);
			
			$this->orderRepository = $orderRepository;
			$this->searchCriteriaBuilder = $searchCriteriaBuilder;		
			$this->collectionFactory = $collectionFactory;			
			$this->resultPageFactory = $resultPageFactory;			
			$this->_orderRequestQueueFactory = $orderRequestQueueFactory;			
		}    
		
		/**
			* Save API data in request_queue table
		*/
		public function saveapidata()
		{
			try {
				
				libxml_use_internal_errors(true);
				$postdata = file_get_contents("php://input");
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				
				$responseData = array();				
				if($postdata)
				{					
					$requestData =  $this->convertFormatToArray($postdata,'xml');
					
					if(!in_array($requestData['serviceName'],$this->_availableService))
					{
						throw new \Exception('Servicename with '.$requestData['serviceName'].' does not exist.');
					}				
					
					if(!isset($requestData['requestID']))
					{
						throw new \Exception('Request id is not found');  
					}
					
					$requestId = $requestData['requestID'];
					
					$xml = new \SimpleXMLElement('<'.$requestData['serviceName'].'/>');
					$response = $this->authenticate($requestData);
					$responseStatus = 'Ok';
					$responseData['requestID'] = $requestId;
					$responseData['status'] = 'Ok';
					if($response['ResponseStatus'] == 'Error')
					{
						$responseStatus = 'Error';
						$responseData['status'] = 'Error';
						$responseData['message'] = $response["ResponseMessage"];					 
					}
					
					/****************************** Inventory Update Starts**********************************/
					
					else if($requestData['serviceName'] == Service::INVENTORY_UPDATES)
					{
						$variantSKU = $requestData['SKU'];
						$products = $objectManager->get('Magento\Catalog\Model\Product')
						->getCollection()
						->addAttributeToFilter('variant_sku',$variantSKU)->getFirstItem();					
						
						$Sku= $products['sku'];
						
						if($Sku){
							$data=array();
							$data['quantity']=$requestData['qty'];
							$data['type']=$requestData['type'];
							$data['storeId']=$requestData['storeID'];
							$InventoryModel = $objectManager->create('\Indusa\Webservices\Model\InventoryStore');
							$requestsave = $InventoryModel->saveInventory($data,$Sku,$requestData['serviceName']);
							
							if($requestData['type']!='Store')
							{
								$data['quantity'] = str_replace(',', '', $data['quantity']);
								$productRepository = $objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface');
								$stockRegistry = $objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface');
								$product = $productRepository->get($Sku);
								$stockItem = $stockRegistry->getStockItem($product->getId());
								$stockItem->setData('qty',$data['quantity']);
								if($data['quantity']>0)
								{
									$stockItem->setData('is_in_stock',1);
								}
								else{
									$stockItem->setData('is_in_stock',0);
								}
								
								$stockRegistry->updateStockItemBySku($Sku, $stockItem);
								
								$apiRequestInfo['request_id'] = $requestId;
								$apiRequestInfo['request_type'] = $requestData['serviceName'];
								$apiRequestInfo['request_xml'] =  $postdata;
								$apiRequestInfo['request_datetime'] =  date('Y-m-d H:i:s');
								$apiRequestInfo['created_at'] = date('Y-m-d H:i:s');
								$apiRequestInfo['updated_at'] = date('Y-m-d H:i:s');            
								$apiRequestInfo['processed'] = 1;            
								$apiRequestInfo['processed_at'] = date('Y-m-d H:i:s'); 
								$apiRequestInfo['processed_list'] = $variantSKU;
								$apiRequestInfo['acknowledgment'] = 1;   
								$apiRequestInfo['ack_datetime'] = date('Y-m-d H:i:s');      
								$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
								$requestsave = $model->saveRequestQueue($apiRequestInfo);								
							}
							else
							{	
								$AXstorearray = array();
								$storecollection = $this->collectionFactory->create()->addFieldToFilter('is_active', 1)->setOrder('creation_time', 'ASC');
								foreach ($storecollection as $strdata) {
									
									foreach ($storecollection as $strdata) {
										$AXstorearray[] = $strdata->getData('ax_storeid');
									}
									
								}								
								if(in_array($requestData['storeID'],$AXstorearray))
								{
									$apiRequestInfo['request_id'] = $requestId;
									$apiRequestInfo['request_type'] = $requestData['serviceName'];
									$apiRequestInfo['request_xml'] =  $postdata;
									$apiRequestInfo['request_datetime'] =  date('Y-m-d H:i:s');
									$apiRequestInfo['created_at'] = date('Y-m-d H:i:s');
									$apiRequestInfo['updated_at'] = date('Y-m-d H:i:s');            
									$apiRequestInfo['processed'] = 1;            
									$apiRequestInfo['processed_at'] = date('Y-m-d H:i:s'); 
									$apiRequestInfo['processed_list'] = $variantSKU;
									$apiRequestInfo['acknowledgment'] = 1;   
									$apiRequestInfo['ack_datetime'] = date('Y-m-d H:i:s');  
									$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
									$requestsave = $model->saveRequestQueue($apiRequestInfo);									
								}
								else{
									$responseData['status'] = 'Error';
									$responseData['message'] = "storeID is invalid";
								}
							}
						}
						else
						{
							$responseData['status'] = 'Error';
							$responseData['message'] = "Requested product doesn't exist";
							$apiRequestInfo['request_id'] = $requestId;
							$apiRequestInfo['request_type'] = $requestData['serviceName'];
							$apiRequestInfo['request_xml'] =  $postdata;
							$apiRequestInfo['request_datetime'] =  date('Y-m-d H:i:s');
							$apiRequestInfo['created_at'] = date('Y-m-d H:i:s');
							$apiRequestInfo['updated_at'] = date('Y-m-d H:i:s');            
							$apiRequestInfo['processed'] = 1;            
							$apiRequestInfo['processed_at'] = date('Y-m-d H:i:s'); 
							$apiRequestInfo['acknowledgment'] = 1;   
							$apiRequestInfo['ack_datetime'] = date('Y-m-d H:i:s');  
							$apiRequestInfo['request_id'] = $requestId;
							$apiRequestInfo['error_list'] = $variantSKU." Requested Sku Doesn't exist" ;
							$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
							$requestsave = $model->saveRequestQueue($apiRequestInfo);
						}
					}	
					else if($requestData['serviceName'] == Service::RESERVED_INVENTORY_UPDATES)
					{	
						$this->reservedInventoryUpdates($requestData,$postdata,$objectManager);
					}
					else if($requestData['serviceName'] == Service::SEND_ACKNOWLEDGEMENT_TO_MAGENTO){
						
						$apiResponseInfo = array();		
						$finalProcessedList = array();
						$requestModel = $this->_orderRequestQueueFactory->create()->getCollection()->addFieldToFilter("request_id",$requestId)->getFirstItem();
						
						if($requestModel->getData("acknowledgment") == 0 ){
							//check if one order data in XML						
							if(count($requestData['processedList']) > 0){	
								
								if(is_array($requestData['processedList']['magentoOrderID'])){
									if(array_key_exists(0,$requestData['processedList']['magentoOrderID']))
									{
										$finalProcessedList = $requestData['processedList']['magentoOrderID'];
									}
									else
									{
										$finalProcessedList[] = $requestData['processedList']['magentoOrderID'];
									}
								}
								else{
									$finalProcessedList[] = $requestData['processedList']['magentoOrderID'];
								}
								
								//update order sync flag in magento if successfully sync at AX						
								foreach($finalProcessedList as $increment_id){
									$this->setOrderSync($increment_id,$objectManager,1);							
								}
								$apiResponseInfo['processed_list'] = json_encode($finalProcessedList);
							}
							else{
								$apiResponseInfo['processed_list'] = "-";
							}
							
							//Error List	
							if(count($requestData['errorList']) > 0){
								if(array_key_exists(0,$requestData['errorList']['error']))
								{
									$errors = $requestData['errorList']['error'];
								}
								else
								{
									$errors[] = $requestData['errorList']['error'];
								}													
								foreach($errors as $error){
									//$error['orderID'] = $error['magentoOrderID'];
									//$error['errorMessage'] = $error['errorMessage'];
									$finalErrorList[] = $error;
								}
								//update order sync flag in magento if successfully sync at AX						
								foreach($finalErrorList as $errorList){
									$this->setOrderSync($errorList['magentoOrderID'],$objectManager,2);							
								}
								$apiResponseInfo['error_list'] = json_encode($finalErrorList);
							}
							else{
								$apiResponseInfo['error_list'] = "-";
							}
							
							//Update order request queue table
							$apiResponseInfo['id'] = $requestModel->getData("id");
							$apiResponseInfo['acknowledgment'] = 1;
							$apiResponseInfo['ack_datetime'] = date('Y-m-d H:i:s');
							$apiResponseInfo['updated_at'] = date('Y-m-d H:i:s');					
							
							//Updating api response data in order_request_queue table
							$orderRequestQueueModel = $objectManager->create('Indusa\Webservices\Model\OrderRequestQueue');
							$orderRequestSave = $orderRequestQueueModel->updateOrderProcessQueue($apiResponseInfo);	
							
							//response give back to AX on sendAcknowledgementToMagento API call
							if(!$orderRequestSave) {  
								$responseData['status'] = 'Error';
								$responseData['message'] = 'Sync Failed';
							}
						}
						//If Already received Acknowledgement
						else{
							$responseData['status'] = 'Error';
							$responseData['message'] = 'Already received Acknowledgement';
						}
					}
					else if($requestData['serviceName'] == Service::ORDER_STATUS_UPDATES)
					{							
						if($requestData['status'] == Service::INVOICED || $requestData['status'] == Service::DELIVERED|| $requestData['status'] == 'Canceled'){
							$orderExist = $this->checkOrder($requestData['magentoOrderID'],$objectManager);
							
							if($orderExist)
							{
								if($requestData['status'] == "Canceled")
								{
									$order = $objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($requestData['magentoOrderID']);
									//$orderID = $order->getId();
									if($order->canCancel()){
										$order->cancel();
										$order->save();
									}
								}
								
								if($requestData['status'] == "Delivered"){
									$responseData = $this->createOrderShipment($requestData['magentoOrderID'],$objectManager,$requestId);		
								}
								elseif($requestData['status'] == "Invoiced"){
									$responseData = $this->createOrderInvoice($requestData['magentoOrderID'],$objectManager,$requestId);									
								}
								if($responseData['status'] == "Ok"){
									$apiRequestInfo['request_id'] = $requestId;
									$apiRequestInfo['request_type'] = $requestData['serviceName'];
									$apiRequestInfo['request_xml'] =  $postdata;
									$apiRequestInfo['request_datetime'] =  date('Y-m-d H:i:s');
									$apiRequestInfo['created_at'] = date('Y-m-d H:i:s');
									$apiRequestInfo['updated_at'] = date('Y-m-d H:i:s');            
									$apiRequestInfo['processed'] = 1;            
									$apiRequestInfo['processed_at'] = date('Y-m-d H:i:s'); 
									$apiRequestInfo['processed_list'] = $requestData['magentoOrderID'];
									$apiRequestInfo['acknowledgment'] = 1;   
									$apiRequestInfo['ack_datetime'] = date('Y-m-d H:i:s');  
									$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
									$requestsave = $model->saveRequestQueue($apiRequestInfo);
								}
							}
							else
							{
								$responseData['status'] = 'Error';
								$responseData['message'] = 'Magento Order Id doesn\'t exist';
							}
						}
						else{
							$responseData['status'] = 'Error';
							$responseData['message'] = 'Invalid Status'; 
						}
					}
					
					else if($requestData['serviceName'] == Service::PRODUCT_STATUS_UPDATES)
					{
						
						$products = $objectManager->get('Magento\Catalog\Model\Product')
						->getCollection()
						->addAttributeToFilter('sku',$requestData['axProductID'])->getFirstItem();					
						
						$Sku= $products['sku'];
						if($Sku){
							
							//changing product status disabled							
							$productRepository = $objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface');
							$productModel = $productRepository->get($requestData['axProductID'], true, 0, true);
							$productModel->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
							$productModel->save();	
							
							$apiRequestInfo['request_id'] = $requestId;
							$apiRequestInfo['request_type'] = $requestData['serviceName'];
							$apiRequestInfo['request_xml'] =  $postdata;
							$apiRequestInfo['request_datetime'] =  date('Y-m-d H:i:s');
							$apiRequestInfo['created_at'] = date('Y-m-d H:i:s');
							$apiRequestInfo['updated_at'] = date('Y-m-d H:i:s');            
							$apiRequestInfo['processed'] = 1;            
							$apiRequestInfo['processed_at'] = date('Y-m-d H:i:s'); 
							$apiRequestInfo['processed_list'] = $requestData['axProductID'];
							$apiRequestInfo['acknowledgment'] = 1;   
							$apiRequestInfo['ack_datetime'] = date('Y-m-d H:i:s');      
							$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
							$requestsave = $model->saveRequestQueue($apiRequestInfo);
						}
						else{
							$responseData['status'] = 'Error';
							$responseData['message'] = "Requested product doesn't exist";
							$apiRequestInfo['request_id'] = $requestId;
							$apiRequestInfo['request_type'] = $requestData['serviceName'];
							$apiRequestInfo['request_xml'] =  $postdata;
							$apiRequestInfo['request_datetime'] =  date('Y-m-d H:i:s');
							$apiRequestInfo['created_at'] = date('Y-m-d H:i:s');
							$apiRequestInfo['updated_at'] = date('Y-m-d H:i:s');            
							$apiRequestInfo['processed'] = 1;            
							$apiRequestInfo['processed_at'] = date('Y-m-d H:i:s'); 
							$apiRequestInfo['acknowledgment'] = 1;   
							$apiRequestInfo['ack_datetime'] = date('Y-m-d H:i:s');  
							$apiRequestInfo['request_id'] = $requestId;
							$apiRequestInfo['error_list'] = $requestData['axProductID']." Requessted Sku Doesn't exist" ;
							$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
							$requestsave = $model->saveRequestQueue($apiRequestInfo);
						}
					}	
					else
					{
						$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
						$baseUrl = $storeManager->getStore()->getBaseUrl();
						
						$apiRequestInfo['request_id'] = $requestId;
						$apiRequestInfo['request_type'] = $requestData['serviceName'];
						$apiRequestInfo['request_xml'] =  $postdata;
						$apiRequestInfo['request_datetime'] =  date('Y-m-d H:i:s');
						$apiRequestInfo['created_at'] = date('Y-m-d H:i:s');
						$apiRequestInfo['updated_at'] = date('Y-m-d H:i:s');            
						
						$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
						$requestsave = $model->saveRequestQueue($apiRequestInfo);
						
						if(!$requestsave) {  
							$responseData['status'] = 'Error';
							$responseData['message'] = 'Sync Failed'; 
						}
					}    
				}  
			} catch (\Exception $e)
			{
				if(!isset($requestData['serviceName']))
				{
					$requestData['serviceName'] = 'Error'; 
				}
				$responseData['status'] = 'Error';
				$responseData['message'] = $e->getMessage(); 
			}
			$xmldata = '<'.$requestData['serviceName'].'>';
			foreach($responseData as $key => $value)
			{
				$xmldata .= '<'.$key.'>'.$value.'</'.$key.'>'; 
			}
			$xmldata .= '</'.$requestData['serviceName'].'>';
			echo $xmldata; 
			die();			
		}    
		
		/**
			* Function to validate the API credentials
			* 
			* @return Response
		*/
		public function authenticate($requestData)
		{
			$validateJsonData = true;	
			
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');      
			
			$configPath = 'ecommerce_webservice/webservice_credential/username';
			$username =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			$configPath = 'ecommerce_webservice/webservice_credential/password';
			$password =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
			$response["ResponseStatus"] = 'success';
			
			if (!isset($requestData['username'])) {
				$validateJsonData = false;
				$response["ResponseStatus"] = 'Error';
				$ResponseMessage[] = 'Username is required';			
			}
			
			if (!isset($requestData['password'])) {
				$validateJsonData = false;
				$response["ResponseStatus"] = 'Error';
				$ResponseMessage[] = 'Password cannot be blank';				
			}
			
			if(!$validateJsonData)
			{
				$response["ResponseMessage"] = implode(" and ",$ResponseMessage);
			}
			
			if (isset($requestData['username']) && isset($requestData['password'])) {
				
				if($username != $requestData['username']) { $response["ResponseStatus"] = 'Error';  $validateJsonData = false; }
				if($password != $requestData['password']) { $response["ResponseStatus"] = 'Error'; $validateJsonData = false;  }
				
				if(!$validateJsonData)
				{
					$response["ResponseMessage"] = 'Username or Password is incorrect';
				}	
			}            
			return $response;	
		}
		
		public function convertFormatToArray($data,$formatType = 'json')
		{
			if($formatType == 'xml')
			{
				$xmlData = simplexml_load_string($data);
				
				if ($xmlData === false) {
					throw new \Exception('Invalid request data');
					return $xmlData;
				}           
				$requestData = json_decode(json_encode((array) simplexml_load_string($data)),1);
			}
			
			if($formatType == 'json')
			{
				$jsondata = json_decode($data,true);
				$requestData = $jsondata['params'];
			}        
			return  $requestData ;
		}
		
		public function createOrderShipment($incrementId,$objectManager,$requestId){
			
			$responseData = array();
			// Load the order
			$order = $objectManager->create('Magento\Sales\Model\Order')
			->loadByAttribute('increment_id', $incrementId);
			
			if(!$order->hasShipments()){
				// Check if order can be shipped or has already shipped
				if (! $order->canShip()) {
					throw new \Magento\Framework\Exception\LocalizedException(
					__('You can\'t create an shipment.')
					);
				}			
				// Initialize the order shipment object
				$convertOrder = $objectManager->create('Magento\Sales\Model\Convert\Order');
				$shipment = $convertOrder->toShipment($order);
				
				// Loop through order items
				foreach ($order->getAllItems() AS $orderItem) {
					// Check if order item has qty to ship or is virtual
					if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
						continue;
					}
					
					$qtyShipped = $orderItem->getQtyToShip();
					
					// Create shipment item with qty
					$shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
					
					// Add shipment item to shipment
					$shipment->addItem($shipmentItem);
				}
				
				// Register shipment
				$shipment->register();
				
				$shipment->getOrder()->setIsInProcess(true);
				
				try {
					// Save created shipment and order
					$shipment->save();
					$shipment->getOrder()->save();
					
					// Send email
					$objectManager->create('Magento\Shipping\Model\ShipmentNotifier')
					->notify($shipment);
					
					$shipment->save();
					
					$responseData['requestID'] = $requestId;
					$responseData['status'] = 'Ok';
					return $responseData;
					
					} catch (\Exception $e) {
					throw new \Magento\Framework\Exception\LocalizedException(
					__($e->getMessage())
					);
				}
			}
			else{
				$responseData['requestID'] = $requestId;
				$responseData['status'] = 'Error';
				$responseData['message'] = 'Shipment exist'; 
				return $responseData;
			}
		}
		public function createOrderInvoice($incrementId,$objectManager,$requestId){
			
			$responseData = array();
			// Load the order
			$order = $objectManager->create('Magento\Sales\Model\Order')
			->loadByAttribute('increment_id', $incrementId);
			
			if(!$order->hasInvoices()){
				if ($order->canInvoice()) {
					// Create invoice for this order
					$invoice = $objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);
					
					// Make sure there is a qty on the invoice
					if (!$invoice->getTotalQty()) {
						throw new \Magento\Framework\Exception\LocalizedException(
						__('You can\'t create an invoice without products.')
						);
					}
					
					// Register as invoice item
					$invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
					$invoice->register();
					
					$invoice->getOrder()->setIsInProcess(true);
					
					// Save the invoice to the order
					$transaction = $objectManager->create('Magento\Framework\DB\Transaction')
					->addObject($invoice)
					->addObject($invoice->getOrder());
					
					$transaction->save();
					
										
					// Magento\Sales\Model\Order\Email\Sender\InvoiceSender
					$invoiceSender = $objectManager->create('Magento\Sales\Model\Order\Email\Sender\InvoiceSender');
					$invoiceSender->send($invoice);
						
						$order->addStatusHistoryComment(
						__('Notified customer about invoice #%1.', $invoice->getId())
						)
						->setIsCustomerNotified(true)
					->save();
					
					$responseData['requestID'] = $requestId;
					$responseData['status'] = 'Ok';
					return $responseData;
				}
			}
			else{
				$responseData['requestID'] = $requestId;
				$responseData['status'] = 'Error';
				$responseData['message'] = 'Invoice exist'; 
				return $responseData;
			}
		}
		
		public function setOrderSync($increment_id,$objectManager,$result)
		{ 
			$order = $objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($increment_id);
			
			$order->setSync($result);
			$order->setSyncAt(date('Y-m-d H:i:s'));
			$order->save();
			
		}
		
		public function checkOrder($incrementId,$objectManager)
		{
			$order = $objectManager->create('Magento\Sales\Model\Order')
			->loadByAttribute('increment_id', $incrementId);
			if($order->getId())
			{
				return true;
			}
			else{
				return false;
			}
		}
		public function reservedInventoryUpdates($productData,$postdata,$objectManager){
			$finalErrorList = array();	
			$finalProcessList = array();	
			
			if(array_key_exists(0,$productData['products']['product']))
			{
				$xmlProducts = $productData['products']['product'];
			}
			else
			{
				$xmlProducts[] =  $productData['products']['product'];
			}
			
			foreach($xmlProducts as $_product){			
				
				$errorList = array();
				$processList = array();
				
				$variantSKU = $_product['sku'];
				
				$products = $objectManager->get('Magento\Catalog\Model\Product')
				->getCollection()
				->addAttributeToFilter('variant_sku',$variantSKU)->getFirstItem();					
				
				$Sku= $products['sku'];

				if($Sku){
				$finalProcessList[] = $Sku;					
					
					$InventoryModel = $objectManager->create('\Indusa\Webservices\Model\InventoryStore');					
					//Saving inventory data
					if($_product['inventories'])
					{
						if(array_key_exists(0,$_product['inventories']['inventory']))
						{
							$storeInventoryInfo = $_product['inventories']['inventory'];
						}
						else
						{
							$storeInventoryInfo[] =  $_product['inventories']['inventory'];
						}
						$Warehouseqty = $InventoryModel->saveInventory($storeInventoryInfo,$Sku);
					}			
					
					$productRepository = $objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface');
					$stockRegistry = $objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface');
					$product = $productRepository->get($Sku);
					$stockItem = $stockRegistry->getStockItem($product->getId());
					$stockItem->setData('qty',$Warehouseqty);
					if($Warehouseqty > 0)
					{
						$stockItem->setData('is_in_stock',1);
					}
					else{
						$stockItem->setData('is_in_stock',0);
					}
					
					$stockRegistry->updateStockItemBySku($Sku, $stockItem);		
					
					$processList['variant_sku'] = $variantSKU;	
					$finalProcessList[] = $processList;		
				}
				else{				
					$errorList['variant_sku'] = $variantSKU;	
					$errorList['message'] ="Requested SKU doesn't exist";
					$finalErrorList[] = $errorList;							
				}
			}			
			//Adding data in request queue table			
			$apiRequestInfo['request_id'] = $productData['requestID'];
			$apiRequestInfo['request_type'] = $productData['serviceName'];
			$apiRequestInfo['request_xml'] =  $postdata;
			$apiRequestInfo['request_datetime'] =  date('Y-m-d H:i:s');
			$apiRequestInfo['created_at'] = date('Y-m-d H:i:s');
			$apiRequestInfo['updated_at'] = date('Y-m-d H:i:s');            
			$apiRequestInfo['processed'] = 1;            
			$apiRequestInfo['processed_at'] = date('Y-m-d H:i:s'); 
			$apiRequestInfo['processed_list'] = json_encode($finalProcessList);
			$apiRequestInfo['error_list'] = json_encode($finalErrorList);
			$apiRequestInfo['acknowledgment'] = 1;   
			$apiRequestInfo['ack_datetime'] = date('Y-m-d H:i:s');   
			
			$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
			$requestsave = $model->saveRequestQueue($apiRequestInfo);				
		}		
		
	}
