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
			->create();
			$orderResult = $this->orderRepository->getList($criteria);
			$order = $orderResult->getItems();
			
			$xmlOrderRequestData = "<eCommerceAPI>";
			$xmlOrderRequestData .= "<username>".$username."</username>";
			$xmlOrderRequestData .= "<password>".$password."</password>";
			$xmlOrderRequestData .= "<requestID>".$requestId."</requestID>";
			$xmlOrderRequestData .= "<serviceName>".Service::CREATE_ORDER_AND_CUSTOMER."</serviceName>";
			$xmlOrderRequestData .= "<orders>";
			
			//$this->logger->info(count($order));
			foreach($order as $orderData)
			{
				$orderPayment = $objectManager->create('Magento\Sales\Model\Order\Payment')->load($orderData->getEntityId());
				
				$xmlOrderRequestData .= "<order>";				
				$billingAddress = $orderData->getBillingAddress();				
				$xmlOrderRequestData .= "<custName>".$orderData->getCustomerFirstName(). " " .$orderData->getCustomerLastName()."</custName>";
				$xmlOrderRequestData .= "<custEmail>".$orderData->getCustomerEmail()."</custEmail>";				
				$xmlOrderRequestData .= "<custPrimaryMobile>".$billingAddress->getTelePhone()."</custPrimaryMobile>";
				$xmlOrderRequestData .= "<magentoCustomerId>".$orderData->getCustomerId()."</magentoCustomerId>";
				$xmlOrderRequestData .= "<custAddressCity>".$billingAddress->getCity()."</custAddressCity>";
				$Street = $billingAddress->getStreet();
				$street = implode("," ,$Street);
				$xmlOrderRequestData .= "<custAddressStreet>".$street."</custAddressStreet>";
				$xmlOrderRequestData .= "<custAddressCountryRegionId>".$billingAddress->getCountryId()."</custAddressCountryRegionId>";
				$xmlOrderRequestData .= "<custAddressState>".$billingAddress->getRegion()."</custAddressState>";
				$xmlOrderRequestData .= "<custContactType>Phone</custContactType>";
				$xmlOrderRequestData .= "<custContactLocator>".$billingAddress->getTelePhone()."</custContactLocator>";
				$xmlOrderRequestData .= "<deliveryDate>13/12/2017</deliveryDate>";
				$xmlOrderRequestData .= "<custAddressLocationName>".$orderData->getCustomerFirstName(). " " .$orderData->getCustomerLastName()."</custAddressLocationName>";
				$xmlOrderRequestData .= "<magentoOrderId>".$orderData->getIncrementId()."</magentoOrderId>";				
				$xmlOrderRequestData .= "<orderDeliveryFrom>".$orderData->getDeliveryFrom()."</orderDeliveryFrom>";
				$xmlOrderRequestData .= "<orderLocationID>".$orderData->getAxStoreId()."</orderLocationID>";
				$xmlOrderRequestData .= "<deliveryDate>".$orderData->getDeliveryDate()."</deliveryDate>";
				$xmlOrderRequestData .= "<orderCreatedAt>".$orderData->getCreatedAt()."</orderCreatedAt>";
				$xmlOrderRequestData .= "<orderCurrencyCode>".$orderData->getOrderCurrencyCode()."</orderCurrencyCode>";
				$payment = $orderData->getPayment();
				$method = $payment->getMethodInstance();
				$xmlOrderRequestData .= "<orderPaymentMode>".$method->getTitle()."</orderPaymentMode>";
				$xmlOrderRequestData .= "<orderTotalAmount>".$orderData->getTotalDue()."</orderTotalAmount>";
				$xmlOrderRequestData .= "<orderItems>";
				$orderItems = $orderData->getAllItems();
				foreach($orderItems as $item){
					$xmlOrderRequestData .= "<item>";
					$xmlOrderRequestData .= "<SKU>SKU".$item->getSku()."</SKU>";
					$xmlOrderRequestData .= "<salesPrice>".$item->getBasePrice()."</salesPrice>";
					$xmlOrderRequestData .= "<quantityOrdered>".$item->getQtyOrdered()."</quantityOrdered>";
					$xmlOrderRequestData .= "<lineAmount>".$item->getPrice()."</lineAmount>";
					$xmlOrderRequestData .= "<lineDiscount>".$item->getDiscountAmount()."</lineDiscount>";
					$xmlOrderRequestData .= "</item>";
				}
				$xmlOrderRequestData .= "</orderItems>";
				
				//Added Payment data if payment method is Knet
				if($method->getTitle() == "Knet"){	
					$addtionalData = json_decode($orderPayment->getAdditionalData(),true);
					
					$xmlOrderRequestData .= "<knetPaymentTransactionData>";
					$xmlOrderRequestData .="<paymentID>".$addtionalData["PaymentID"]."</paymentID>";
					$xmlOrderRequestData .="<tranID>".$addtionalData["TranID"]."</tranID>";
					$xmlOrderRequestData .="<trackID>".$addtionalData["TrackID"]."</trackID>";			
					$xmlOrderRequestData .= "</knetPaymentTransactionData>";
				}
				//Added Payment data if payment method is CC
				elseif($method->getTitle() == "Credit card"){					
					//Code here
				}
				//Added Payment data if payment method is DB
				elseif($method->getTitle() == "DB"){					
					//Code here
				}
				$xmlOrderRequestData .= "</order>";
			}
			$xmlOrderRequestData .= "</orders>";
			$xmlOrderRequestData .= "</eCommerceAPI>";
			
			//$this->logger->info(json_encode($xmlOrderRequestData));
			
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
			 if (array_key_exists("Status",$orderSyncResponse))
        { 
			if($orderSyncResponse['Status'] = "Success"){
			
				//Update Order Response status in order_request_queue table			
				$apiResponseInfo['id'] = $lastRequestId;			
				$apiResponseInfo['response'] = 1;            			
				$apiResponseInfo['response_at'] = date('Y-m-d H:i:s');								
				$responsesave = $OrderRequestQueueModel->updateOrderProcessQueue($apiResponseInfo);	
			}
		}
		}
	}	
?>
