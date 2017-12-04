<?php
	
	namespace Indusa\Webservices\Cron;
	use Indusa\Webservices\Logger\Logger;
	
	class ProcessError {
		
		protected $logger;
		protected $_requestQueueFactory;
		
		public function __construct(
		\Indusa\Webservices\Logger\Logger $loggerInterface, 
		\Indusa\Webservices\Model\RequestQueueFactory $requestQueueFactory
		){
			$this->_requestQueueFactory = $requestQueueFactory;
			$this->logger = $loggerInterface;
		}	
		public function execute() {
			
			$resultFactory = $this->_requestQueueFactory->create()->getCollection()->addFieldToFilter('acknowledgment', array('eq' => 0))->addFieldToFilter('error_list',array('notnull' => true))->addFieldToFilter('request_type', array('eq' =>'manageProducts'))->setOrder('id', 'DESC');
			
			$requestData = $resultFactory->getData();
			
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$processmodel = $objectManager->create('\Indusa\Webservices\Model\Acknowledgment\SendAcknowledgment');
			foreach ($requestData as $_request) {				
				$scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');      
				//Fetching AX Webservice credential
				$configPath = 'ack_webservice/ack_credential/username';
				$username =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
				$configPath = 'ack_webservice/ack_credential/password';
				$password =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
				
				$ackResponse = "<eCommerceAPI>";
				$ackResponse .= "<username>" .$username. "</username>";
				$ackResponse .= "<password>" .$password. "</password>";
				$ackResponse .= "<serviceName>sendAcknowledgementToAX</serviceName>";
				$ackResponse .= "<requestID>" .$_request['request_id']. "</requestID>";
				$ackResponse .= "<status>Error</status>";
				$ackResponse .= "<requestType>" .$_request['request_type']. "</requestType>";
				$ackResponse .= "<processedList/><errorList><errorMessage>" .$_request['error_list']."</errorMessage></errorList></eCommerceAPI>";							
				$errorData = array("acknowledgeXML" => $ackResponse,"requestId" => $_request['request_id']);				
				$processStatus = $processmodel->sendAcknowledgmentToAX($errorData, $_request['id']);				
				
			}
		}		
		
		/**
			* @param xml data $data
			* @param Type of formate to convert $formatType
			* 
			* @return requestData
		*/
		public function convertFormatToArray($data, $formatType = 'json') {
			if ($formatType == 'xml') {
				$requestData = json_decode(json_encode((array) simplexml_load_string($data)), 1);            
			}
			if ($formatType == 'json') {
				$jsondata = json_decode($data, true);
				$requestData = $jsondata['params'];
			}
			return $requestData;
		}
		
	}		