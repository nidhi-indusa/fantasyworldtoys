<?php
	
	namespace Indusa\Webservices\Cron;
	use Indusa\Webservices\Logger\Logger;
	use Indusa\Webservices\Model\Service;
	
	class CreateOrderAndCustomer
	{
		protected $logger;
		protected $orderRepository;
		protected $searchCriteriaBuilder;
		public $_availableService = array();
		
		public function __construct(\Indusa\Webservices\Logger\Logger $loggerInterface , 
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder)
		{
			$this->logger = $loggerInterface;
			$this->orderRepository = $orderRepository;
			$this->searchCriteriaBuilder = $searchCriteriaBuilder;
			$this->_availableService = array(Service::MANAGE_PRODUCTS,Service::INVENTORY_UPDATES,Service::PRICE_UPDATES,Service::RELATED_PRODUCTS,Service::CREATE_ORDER_AND_CUSTOMER,Service::ORDER_STATUS_UPDATES);
		}
		
		public function execute() {	
			
			$orderSyncResponse = array();	
			$orderArray = array();
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$orderRequestQueueModel = $objectManager->create('Indusa\Webservices\Model\OrderRequestQueue');
			
			$resultFactory = $orderRequestQueueModel->getCollection()->getLastItem();
			
			//getting request ID
			if(count($resultFactory) == 0) $requestId = "AXMI-1";
			else $requestId = "AXMI-".($resultFactory->getId()+1);			
			
			$scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');      
			$configPath = 'ack_webservice/ack_credential/username';
			$username =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			$configPath = 'ack_webservice/ack_credential/password';
			$password =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
			$configPath = 'ack_webservice/ack_credential/order_url';
			
			$orderUrl =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
			$criteria = $this->searchCriteriaBuilder
			->addFilter('sync',0)
			->addFilter('sent_to_ax',0)
			->addFilter('status',array('pending','Processing'),'in')
			->create();
			$orderResult = $this->orderRepository->getList($criteria);
			
			$order = $orderResult->getItems();
			$sendCount=0;
			if(count($order)>0)
			{
				$xmlOrderRequestData = "<eCommerceAPI>";
				$xmlOrderRequestData .= "<username>".$username."</username>";
				$xmlOrderRequestData .= "<password>".$password."</password>";
				$xmlOrderRequestData .= "<requestID>".$requestId."</requestID>";
				$xmlOrderRequestData .= "<serviceName>".Service::CREATE_ORDER_AND_CUSTOMER."</serviceName>";
				$xmlOrderRequestData .= "<orders>";
				
				//$this->logger->info(count($order));
				foreach($order as $orderData)
				{
					$orderArray[] = $orderData->getIncrementId();
					$payment = $orderData->getPayment();
					$method = $payment->getMethodInstance();
					if(($method->getTitle()=="Knet") && ($orderData->getStatus()=="processing"))
					{
						
						$xmlOrderRequestData = $this->prepareOrder($orderData,$xmlOrderRequestData,$method,$payment);
						$sendCount++;
						
					}
					else if(($method->getTitle()=="Cash on Delivery")||($method->getTitle()=="Credit Card Payment"))
					{
						
						$xmlOrderRequestData = $this->prepareOrder($orderData,$xmlOrderRequestData,$method,$payment);
						$sendCount++;
					}
				}
				
				$xmlOrderRequestData .= "</orders>";
				$xmlOrderRequestData .= "</eCommerceAPI>";
				
				if($sendCount>0){
					//Add Order Request data in order_request_queue table			
					$apiRequestInfo['request_id'] = $requestId;
					$apiRequestInfo['request_type'] = Service::CREATE_ORDER_AND_CUSTOMER;
					$apiRequestInfo['request_xml'] =  $xmlOrderRequestData;
					$apiRequestInfo['request_datetime'] =  date('Y-m-d H:i:s');
					$apiRequestInfo['created_at'] = date('Y-m-d H:i:s');
					$apiRequestInfo['updated_at'] = date('Y-m-d H:i:s');            
					$apiRequestInfo['response'] = 0;            			
					$apiRequestInfo['acknowledgment'] = 0;   			
					$OrderRequestQueueModel = $objectManager->create('Indusa\Webservices\Model\OrderRequestQueue');
					$lastRequestId = $OrderRequestQueueModel->saveOrderRequestQueue($apiRequestInfo);					
					
					//call AX web service to sync the orders
					
					$orderData = array("orderXML" => $xmlOrderRequestData,"requestId" => $requestId);
					$headers = array("Content-type: application/json","password:$password","username:$username");
					
					$ch = curl_init($orderUrl); 
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");			
					curl_setopt($ch, CURLOPT_POST, true);			
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));			
					
					$orderSyncResponse = json_decode(curl_exec($ch),true); 
					//Log response in Indusa_Webservice.log
					$this->logger->info(json_encode($orderSyncResponse));
					if($orderSyncResponse != null){
						if (array_key_exists("Status",$orderSyncResponse))
						{ 
							if($orderSyncResponse['Status'] == "Success"){
								
								//Update Order Response status in order_request_queue table			
								$apiResponseInfo['id'] = $lastRequestId;			
								$apiResponseInfo['response'] = 1;            			
								$apiResponseInfo['response_at'] = date('Y-m-d H:i:s');
								$responsesave = $OrderRequestQueueModel->updateOrderProcessQueue($apiResponseInfo);
								
								foreach($orderArray as $increment_id){
									$order = $objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($increment_id);
									$order->setSentToAx(1);
									//$order->setSyncAt(date('Y-m-d H:i:s'));
									$order->save();
								}
							}
						}
					}
				}
			}
		}
		
		public function prepareOrder($orderData,$xmlOrderRequestData,$method,$payment){
			$orderArray[] = $orderData->getIncrementId();
			$xmlOrderRequestData .= "<order>";				
			$shippingAddress = $orderData->getShippingAddress();				
			$xmlOrderRequestData .= "<custName>".$orderData->getCustomerFirstName(). " " .$orderData->getCustomerLastName()."</custName>";
			$xmlOrderRequestData .= "<custEmail>".$orderData->getCustomerEmail()."</custEmail>";				
			$xmlOrderRequestData .= "<custPrimaryMobile>".$shippingAddress->getTelePhone()."</custPrimaryMobile>";
			$xmlOrderRequestData .= "<magentoCustomerId>".$orderData->getCustomerId()."</magentoCustomerId>";
			$xmlOrderRequestData .= "<custAddressCity>".$shippingAddress->getCity()."</custAddressCity>";
			$Street = $shippingAddress->getStreet();
			$street = implode("," ,$Street);
			$xmlOrderRequestData .= "<custAddressStreet>".$street."</custAddressStreet>";
			$xmlOrderRequestData .= "<custAddressCountryRegionId>KWT</custAddressCountryRegionId>";
			$xmlOrderRequestData .= "<custAddressState>".$shippingAddress->getRegion()."</custAddressState>";
			$xmlOrderRequestData .= "<custContactType>Phone</custContactType>";
			$xmlOrderRequestData .= "<custContactLocator>".$shippingAddress->getTelePhone()."</custContactLocator>";				
			$xmlOrderRequestData .= "<custAddressLocationName>".$orderData->getCustomerFirstName(). " " .$orderData->getCustomerLastName()."</custAddressLocationName>";
			$xmlOrderRequestData .= "<magentoOrderId>".$orderData->getIncrementId()."</magentoOrderId>";				
			$xmlOrderRequestData .= "<orderDeliveryFrom>".$orderData->getDeliveryFrom()."</orderDeliveryFrom>";
			$xmlOrderRequestData .= "<orderLocationID>".$orderData->getAxStoreId()."</orderLocationID>";
			$xmlOrderRequestData .= "<deliveryDate>".$orderData->getDeliveryDate()."</deliveryDate>";
			$xmlOrderRequestData .= "<orderCreatedAt>".$orderData->getCreatedAt()."</orderCreatedAt>";
			$xmlOrderRequestData .= "<orderCurrencyCode>".$orderData->getOrderCurrencyCode()."</orderCurrencyCode>";
			/* $payment = $orderData->getPayment();
			$method = $payment->getMethodInstance(); */
			$xmlOrderRequestData .= "<orderPaymentMode>".$method->getTitle()."</orderPaymentMode>";
			$xmlOrderRequestData .= "<orderTotalAmount>".$orderData->getGrandTotal()."</orderTotalAmount>";				
			$xmlOrderRequestData .= "<orderItems>";
			$orderItems = $orderData->getAllItems();
			foreach($orderItems as $item){
				
				if($item->getProductType() == "configurable"){					
					$basePrice = $item->getBasePrice();
					$price = $item->getPrice();
					$discountAmount = $item->getDiscountAmount();
					$transferOrderQuantity = $item->getTransferOrderQuantity();
					$qtyOrdered = $item->getQtyOrdered();						
				}
				
				if($item->getProductType() == "simple"){
					
					if($item->getParentItemId() == null){
						$basePrice = $item->getBasePrice();
						$price = $item->getPrice();
						$discountAmount = $item->getDiscountAmount();
						$transferOrderQuantity = $item->getTransferOrderQuantity();
						$qtyOrdered = $item->getQtyOrdered();
					}													
					
					//get variant sku
					$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
					$productModel = $objectManager->create('Magento\Catalog\Model\Product')->loadByAttribute("sku",$item->getSku());
					
					$xmlOrderRequestData .= "<item>";
					$xmlOrderRequestData .= "<SKU>".$productModel->getVariantSku()."</SKU>";
					$xmlOrderRequestData .= "<salesPrice>".$basePrice."</salesPrice>";
					$xmlOrderRequestData .= "<quantityOrdered>".$qtyOrdered."</quantityOrdered>";
					$xmlOrderRequestData .= "<lineAmount>".$price."</lineAmount>";
					$xmlOrderRequestData .= "<lineDiscount>".$discountAmount."</lineDiscount>";
					$xmlOrderRequestData .="<transferOrderQuantity>".$transferOrderQuantity."</transferOrderQuantity>";
					$xmlOrderRequestData .= "</item>";
				}
			}
			$xmlOrderRequestData .= "</orderItems>";
			
			//Added Payment data if payment method is Knet
			if($method->getTitle() == "Knet"){	
				$addtionalData = json_decode($payment->getAdditionalData(),true);
				
				$xmlOrderRequestData .= "<knetPaymentTransactionData>";
				$xmlOrderRequestData .="<paymentID>".$addtionalData["PaymentID"]."</paymentID>";
				$xmlOrderRequestData .="<tranID>".$addtionalData["TranID"]."</tranID>";
				$xmlOrderRequestData .="<trackID>".$addtionalData["TrackID"]."</trackID>";			
				$xmlOrderRequestData .= "</knetPaymentTransactionData>";
			}
			//Added Payment data if payment method is CC
			//Added Payment data if payment method is CC
			elseif($method->getTitle() == "Credit Card Payment"){
				$additionalInformation =  $payment->getAdditionalInformation();
				
				$xmlOrderRequestData .= "<cybersourcePaymentTransactionData>";
				$xmlOrderRequestData .="<tranID>".$payment->getLastTransID()."</tranID>";
				$xmlOrderRequestData .="<Last4>".$additionalInformation["last4"]."</Last4>";
				
				$cardType = $additionalInformation['cardType'];
				
				if($cardType==001)
				{
					$xmlOrderRequestData .="<cardType>Visa</cardType>";
				}
				
				else if($cardType==002)
				{
					$xmlOrderRequestData .="<cardType>MasterCard</cardType>";
				}
				else if($cardType==003)
				{
					$xmlOrderRequestData .="<cardType>American Express</cardType>";
				}
				else if($cardType==004)
				{
					$xmlOrderRequestData .="<cardType>Discover</cardType>";
				}
				
				
				$xmlOrderRequestData .="<cardExpiry>".$payment->getCcExpMonth() . "-" . $payment->getCcExpYear()."</cardExpiry>";			
				$xmlOrderRequestData .= "</cybersourcePaymentTransactionData>";
				
				//Code here
			}
			//Added Payment data if payment method is DB
			elseif($method->getTitle() == "DB"){					
				//Code here
			}
			$xmlOrderRequestData .= "</order>";
			
			return $xmlOrderRequestData;
			
		}
	}	
?>
