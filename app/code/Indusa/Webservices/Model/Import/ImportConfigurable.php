<?php
	
	namespace Indusa\Webservices\Model\Import;
	
	use Magento\Backend\App\Area\FrontNameResolver;
	use Magento\Framework\App\ObjectManager\ConfigLoader;
	use Magento\Framework\App\ObjectManagerFactory;
	use Magento\Framework\App\State;
	use Magento\ImportExport\Model\Import;
	use Magento\Store\Model\Store;
	use Magento\Store\Model\StoreManager;
	use Indusa\Webservices\Logger\Logger; 
	use Indusa\Webservices\Model\Service;	
	
	class ImportConfigurable extends \Magento\Framework\Model\AbstractModel {
		
		protected $objectManager;
		private $objectManagerFactory;
		protected $_productFactory;
		protected $logger;
		protected $categoryLinkManagement;
		/**
			* @param Magento\Framework\App\ObjectManagerFactory $objectManagerFactory
			* 
		*/
		public function __construct(ObjectManagerFactory $objectManagerFactory,\Magento\Catalog\Model\ProductFactory $productFactory, \Indusa\Webservices\Logger\Logger $loggerInterface,  \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement ) {
			$this->objectManagerFactory = $objectManagerFactory;
			$this->_productFactory = $productFactory;
			$this->logger = $loggerInterface;
			$this->categoryLinkManagement = $categoryLinkManagement;
		}
        
		/**
			* @param product requested data $productData
			* @param id of request que process $processId
			* 
			* @return boolean
		*/
		public function importProductData($productData,$processId,$request_id) 
		{        
			$omParams = $_SERVER;
			$omParams[StoreManager::PARAM_RUN_CODE] = 'admin';
			$omParams[Store::CUSTOM_ENTRY_POINT_PARAM] = true;
			$this->objectManager = $this->objectManagerFactory->create($omParams);
			
			$area = FrontNameResolver::AREA_CODE;
			
			/**  @var \Magento\Framework\App\State $appState */
			$appState = $this->objectManager->get('Magento\Framework\App\State');
			$appState->setAreaCode($area);
			$configLoader = $this->objectManager->get('Magento\Framework\ObjectManager\ConfigLoaderInterface');
			$this->objectManager->configure($configLoader->load($area));   
			
			$simpleProductArr = $this->getMapEntities($productData);
			
			$productsArray = $simpleProductArr;
			
			$importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');
			$apiResponse = array();
			$processList = array();
			
			try {
				
				$importerModel->setBehavior(Import::BEHAVIOR_APPEND);
				$importerModel->setEntityCode('catalog_product');  
				
				/*
					* For Configurable product
				*/    		
				
				
				$adapterFactory = $this->objectManager->create('FireGento\FastSimpleImport\Model\Adapters\NestedArrayAdapterFactory');
				$importerModel->setImportAdapterFactory($adapterFactory);
				$processed = $importerModel->processImport($simpleProductArr,$processId);
				
				$logTracing = $importerModel->getLogTrace();
				$this->logger->info($logTracing);
				
				if($processed  === true){   
					
					$scopeConfig = $this->objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');      
					$configPath = 'ack_webservice/ack_credential/username';
					$username =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
					$configPath = 'ack_webservice/ack_credential/password';
					$password =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
					
					$ackResponse = "<eCommerceAPI>";
					$ackResponse .= "<username>" .$username. "</username>";
					$ackResponse .= "<password>" .$password. "</password>";
					$ackResponse .= "<serviceName>" .Service::SEND_ACKNOWLEDGEMENT_TO_AX. "</serviceName>";
					$ackResponse .= "<requestID>" .$request_id. "</requestID>";
					$ackResponse .= "<status>Success</status>";
					$ackResponse .= "<requestType>" .Service::MANAGE_PRODUCTS. "</requestType>";
					$ackResponse .= "<processedList>";
					
					if(array_key_exists(0,$productData['products']['product']))
					{
						$xmlProudcts = $productData['products']['product'];
					}
					else
					{
						$xmlProudcts[] =  $productData['products']['product'];
					}
					
					foreach($xmlProudcts as $_product)
					{
						$processListSku[] = $_product['axProductID'];		
						$ackResponse .="<axProductID>".$_product['axProductID']."</axProductID>";
					}
					//Updating processing list
					$Ackprocess['processed'] = 1;
					$Ackprocess['processed_at'] = date('Y-m-d H:i:s');
					$Ackprocess['processed_list'] = json_encode($processListSku);
					$Ackprocess['error_list'] = NULL;
					$Ackprocess['id'] = $processId;   
					$model = $this->objectManager->create('Indusa\Webservices\Model\RequestQueue');
					$requestsave = $model->updateProcessQueue($Ackprocess);
					
					//SendAcknowledgmentToAX
					$ackResponse .="</processedList><errorList/></eCommerceAPI>";
					$successData = array("acknowledgeXML" => $ackResponse,"requestId" =>$request_id);
					$processmodel = $this->objectManager->create('\Indusa\Webservices\Model\Acknowledgment\SendAcknowledgment');
					
					$processStatus = $processmodel->sendAcknowledgmentToAX($successData, $processId);
					
					return true;						
				}			
				} catch (Exception $e) {
				return false;
			}        
		}      
		/**
			* @param simple product array simpleProductArr 
			* @return array
		*/
		public function getMapEntities($simpleProductArr)
		{
			$simpleProductqty = 0;
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$AttributeModel = $objectManager->create('\Indusa\Webservices\Model\Attribute');
			$InventoryModel = $objectManager->create('\Indusa\Webservices\Model\InventoryStore');
			$products = [];
			$CategoryModel = $objectManager->create('\Indusa\Webservices\Model\Service');
			
			//check if one product data in XML	
			if(array_key_exists(0,$simpleProductArr['products']['product']))
			{
				$xmlProudcts = $simpleProductArr['products']['product'];
			}
			else
			{
				$xmlProudcts[] =  $simpleProductArr['products']['product'];
			}
			
			foreach($xmlProudcts as $_product)
			{				
				if(!array_key_exists("BRAND",$_product)) $_product['BRAND'] = "";
				if(!array_key_exists("Gender",$_product)) $_product['Gender'] = "";
				if(!array_key_exists("LICENSE",$_product)) $_product['LICENSE'] = "";
				if(!array_key_exists("description",$_product)) $_product['description'] = "";
				if(!array_key_exists("specifications",$_product)) $_product['specifications'] = "";
				if(!array_key_exists("additionalInfo",$_product)) $_product['additionalInfo'] = "";
				if(!array_key_exists("AGE",$_product)) $_product['AGE'] = "";
				
				$weightInGram = $_product['weight'];
				$weightInlbs = $weightInGram * 0.002205;
				$varianAttArr = array();	
				$variantProducts = array();	
				$configurable_variations = array();			
				
				//===================create simple product==========================//
				$variants = $_product['variants']['variant'];
				if(array_key_exists(0,$variants)){ $variantProducts = $variants; } else { $variantProducts[] =  $variants; }
				$catIds =  $_product['productCategories']['productCategory'];
				$categories = $CategoryModel->getCategory($catIds);
				foreach($variantProducts as $_variantProducts)  
				{
					
					$_simpleproducts = array(); 	
					if($_variantProducts['variantAttributes'])
					{
						if(array_key_exists(0,$_variantProducts['variantAttributes']['variantAttribute']))
						{
							$varianAttArr = $_variantProducts['variantAttributes']['variantAttribute'];
						}
						else
						{
							$varianAttArr[] =  $_variantProducts['variantAttributes']['variantAttribute'];
						}    
						$attributeArr = $AttributeModel->mappingAttribute($varianAttArr);       					
					}
					
					$SKU = $_product['axProductID'];	
					$variantName = $_product['name'];	
					
					if(isset($attributeArr['size'])){ 
						$SKU .= '-'.$attributeArr['size'];
						$variantName = $_product['name']."-".$attributeArr['size'];
					}
					
					if(isset($attributeArr['color'])){ 
						$SKU .= '-'.$attributeArr['color'];
						if(isset($variantName)) $variantName = $variantName."-".$attributeArr['color'];
						else $variantName = $_product['name']."-".$attributeArr['color'];
					}
					$_simpleproducts = array(
					'sku' => $SKU,   
					'variant_sku' => $_variantProducts['variantSKU'],
					'attribute_set_code' => 'Default',
					'product_type' => 'simple',
					'status' => 1,
					'categories' => $categories, 
					'description' => $_product['description'],
					'product_websites' => 'base',
					'name' => isset($variantName) ? $variantName : $_product['name'],
					'specification' => $_product['specifications'],
					'additional_info' => $_product['additionalInfo'],
					'weight' => $weightInlbs,					
					'reserved_qty' => $_variantProducts['variantReservedQty'],					
					'manufacture_id' => $attributeArr['style'],
					'manufacture_code' => $attributeArr['config'],
					'license' => $_product['LICENSE'],
					'gender' => $_product['Gender'],
					'manufacturer' => $_product['BRAND'],
					'url_key' => $SKU
					);
					
					$product = $this->_productFactory->create();				
					$existingProductID = $product->getIdBySku($SKU);
					
					
					// -- check if product exists then inventory and price won't be updated			
					if(!$existingProductID){
						
						//Price updates if product does not exists						
						$_simpleproducts['price'] = str_replace(',', '', $_product['price']);
						//$_simpleproducts['special_price'] = str_replace(',', '', $_product['specialPrice']);
						if($_product['specialPrice'] == 0.00){
						$_simpleproducts['special_price'] = "";
					}
						else{
						$_simpleproducts['special_price'] = str_replace(',', '', $_product['specialPrice']);
						}
						
						if($_variantProducts['inventories'])
						{
							if(array_key_exists(0,$_variantProducts['inventories']['inventory']))
							{
								$storeInventoryInfo = $_variantProducts['inventories']['inventory'];
							}
							else
							{
								$storeInventoryInfo[] =  $_variantProducts['inventories']['inventory'];
							}
							$simpleProductqty = $InventoryModel->saveInventory($storeInventoryInfo,$SKU);
						}
						//Inventory updates if product does not exists
						$_simpleproducts['qty'] = $simpleProductqty;  
					}
					
					else
					{
						$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
						
						$categorycollection = $objectManager->get('Magento\Catalog\Model\CategoryFactory')->create()->getCollection()
						->addFieldToFilter('ax_category_code', ['in' => $catIds]);
						$categoryid = array();
						foreach($categorycollection as $_category)
						{  
							$categoryid[] = $_category->getId();	
						}	
						$productId = $objectManager->get('Magento\Catalog\Model\Product')->getIdBySku($SKU);
						
						$product = $this->_productFactory->create()->load($productId);
						$product->setCategoryIds($categoryid);
						$this->categoryLinkManagement->assignProductToCategories($product->getSku(), $product->getCategoryIds());
						
					}	
					
					$varianConfigProductArr = array();
					
					if(isset($attributeArr['size']))
					{
						$varianConfigProductArr['size'] = $attributeArr['size'];
						$varianConfigProductArr['sku'] = $SKU;
					}
					
					if(isset($attributeArr['color']))
					{
						$varianConfigProductArr['color'] = $attributeArr['color'];
						$varianConfigProductArr['sku'] = $SKU;
					}
					
					if(isset($varianConfigProductArr['sku']))
					{
						$configurable_variations[] = $varianConfigProductArr; 
						$_simpleproducts['visibility'] = 'Not Visible Individually';
					}
					else{
						$_simpleproducts['is_newproduct'] = $_product['new'];			
						$_simpleproducts['is_featured'] = $_product['featured'];
						$_simpleproducts['is_seller'] = $_product['hotSeller']; 
						$_simpleproducts['website_only'] = $_product['online']; 
						$_simpleproducts['is_homedelivery'] = $_product['onlyHD'];
						$_simpleproducts['age_group'] = $_product['AGE'];					
						$_simpleproducts['video'] = isset($_product['videoURL'])? $_product['videoURL'] :'';
						$_simpleproducts['visibility'] = 'Catalog, Search';
						
					}					
					//creating simple products
					$configProduct[] = array_merge($_simpleproducts,$attributeArr); 					
				}
				
				if(isset($configurable_variations) && is_array($configurable_variations))
				{
					$existingConfigurableProduct = $objectManager->create('\Magento\Catalog\Model\Product')->loadByAttribute('sku', $_product['axProductID']);
					
					// -- check if product exists then price should be same as before
					if(!$existingConfigurableProduct){
						
						$price  = str_replace(',', '', $_product['price']);
						//$specialPrice  = str_replace(',', '', $_product['specialPrice']);
						if($_product['specialPrice'] == 0.00){
							$specialPrice = "";
					}
						else{
						$specialPrice = str_replace(',', '', $_product['specialPrice']);
						}
						//$specialPrice  = $_product['specialPrice'];
						
						$configProduct[] = array(
						'sku' => $_product['axProductID'],				
						'attribute_set_code' => 'Default',
						'product_type' => 'configurable',
						'product_websites' => 'base',
						'name' => $_product['name'],
						'status' => 1,
						'specification' => $_product['specifications'],
						'additional_info' => $_product['additionalInfo'],
						'price' => 	$price,
						'special_price' => 	$specialPrice,
						'categories' =>$categories,						
						'description' => $_product['description'],
						'weight' => $weightInlbs,
						'is_newproduct' => $_product['new'],				
						'is_featured' =>  $_product['featured'], 
						'is_seller' =>  $_product['hotSeller'] , 
						'website_only' =>  $_product['online'], 
						'is_homedelivery' =>  $_product['onlyHD'],
						'age_group' =>   $_product['AGE'],
						'license' =>   $_product['LICENSE'],
						'gender' =>    $_product['Gender'],
						'manufacturer' =>    $_product['BRAND'],    
						'video' =>  isset($_product['videoURL'])? $_product['videoURL'] :'',
						'url_key' => $_product['axProductID'],
						'manage_stock' => 0,   
						'use_config_manage_stock' => 0, 
						'use_config_min_qty' => 0,    				
						'configurable_variations' => $configurable_variations
						
						);
						
					}
					else{					
						$price  = $existingConfigurableProduct->getPrice();
						$specialPrice  = $existingConfigurableProduct->getSpecialPrice();
						$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
						
						$categorycollection = $objectManager->get('Magento\Catalog\Model\CategoryFactory')->create()->getCollection()
						->addFieldToFilter('ax_category_code', ['in' => $catIds]);
						$categoryid = array();
						foreach($categorycollection as $_category)
						{  
							$categoryid[] = $_category->getId();	
						}
						
						$productId = $objectManager->get('Magento\Catalog\Model\Product')->getIdBySku($_product['axProductID']);
						
						$product = $this->_productFactory->create()->load($productId);
						$product->setCategoryIds($categoryid);
						$this->categoryLinkManagement->assignProductToCategories($product->getSku(), $product->getCategoryIds());
						
						$configProduct[] = array(
						'sku' => $_product['axProductID'],				
						'attribute_set_code' => 'Default',
						'product_type' => 'configurable',
						'product_websites' => 'base',
						'name' => $_product['name'],
						'status' => 1,
						'specification' => $_product['specifications'],
						'additional_info' => $_product['additionalInfo'],
						'price' => 	$price,
						'special_price' => 	$specialPrice,						
						'description' => $_product['description'],
						'weight' => $weightInlbs,
						'is_newproduct' => $_product['new'],				
						'is_featured' =>  $_product['featured'], 
						'is_seller' =>  $_product['hotSeller'] , 
						'website_only' =>  $_product['online'], 
						'is_homedelivery' =>  $_product['onlyHD'],
						'age_group' =>   $_product['AGE'],
						'license' =>   $_product['LICENSE'],
						'gender' =>    $_product['Gender'],
						'manufacturer' =>    $_product['BRAND'],    
						'video' =>  isset($_product['videoURL'])? $_product['videoURL'] :'',
						'url_key' => $_product['axProductID'],
						'manage_stock' => 0,   
						'use_config_manage_stock' => 0, 
						'use_config_min_qty' => 0,    				
						'configurable_variations' => $configurable_variations
						); 
						
					}
				}
			}
			return $configProduct; 
		}
	}								
