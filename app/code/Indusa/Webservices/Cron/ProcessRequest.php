<?php
	
	namespace Indusa\Webservices\Cron;
	use Indusa\Webservices\Model\Service;
	use Indusa\Webservices\Logger\Logger;
	
	class ProcessRequest {
		
		protected $logger;
		protected $_requestQueueFactory;
		protected $_productFactory;
		protected $_service;
		
		public function __construct(
		\Indusa\Webservices\Logger\Logger $loggerInterface, \Indusa\Webservices\Model\RequestQueueFactory $requestQueueFactory , \Magento\Catalog\Model\ProductFactory $productFactory, \Indusa\Webservices\Model\Service $service		
		) {
			$this->_requestQueueFactory = $requestQueueFactory;
			$this->logger = $loggerInterface;			
			$this->_productFactory = $productFactory;
			$this->_service = $service;
		}
		
		/**
			* indusa_webservices_cron_group Cron execution
			*
		*/
		public function execute() {
			
			$errorList = array();
			$processList = array();
			$finalErrorList = array();				
			$xmlProducts = array();
			
			//Fetching all the request with "processed" value is '0'
			$resultFactory = $this->_requestQueueFactory->create()->getCollection()->addFieldToFilter('request_type', array('eq' =>'manageProducts'))->addFieldToFilter('processed', array('eq' => 0))->addFieldToFilter('error_list', array('null' => true))->setOrder('id', 'DESC');
			
			$requestData = $resultFactory->getData();
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$processmodel = $objectManager->create('\Indusa\Webservices\Model\Acknowledgment\SendAcknowledgment');	
			
			foreach ($requestData as $_request) {
				$requestXML = $_request['request_xml'];
				$requestDataArray = $this->convertFormatToArray($requestXML, 'xml');
				
				$importProductModel = $objectManager->get('Indusa\Webservices\Model\Import\ImportConfigurable');
				$productResponseInfo = $importProductModel->importProductData($requestDataArray, $_request['id'],$_request['request_id']);
				
				if ($productResponseInfo) {										
					//Magento reindexing after product changes
					$this->_service->reIndexing();								
				}
				
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