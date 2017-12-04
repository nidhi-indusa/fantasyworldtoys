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

class ImportConfigurable extends \Magento\Framework\Model\AbstractModel {

    protected $objectManager;
    private $objectManagerFactory;
	protected $_productFactory;
	protected $logger;

    /**
     * @param Magento\Framework\App\ObjectManagerFactory $objectManagerFactory
     * 
     */
    public function __construct(ObjectManagerFactory $objectManagerFactory,\Magento\Catalog\Model\ProductFactory $productFactory, \Indusa\Webservices\Logger\Logger $loggerInterface ) {
        $this->objectManagerFactory = $objectManagerFactory;
		$this->_productFactory = $productFactory;
        $this->logger = $loggerInterface;
    }
        
	/**
     * @param product requested data $productData
     * @param id of request que process $processId
     * 
     * @return boolean
     */
    public function importProductData($productData,$processId) 
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
		
        try {
            
			$importerModel->setBehavior(Import::BEHAVIOR_APPEND);
			$importerModel->setEntityCode('catalog_product');  
			
			/*
			 * For Configurable product
			 */    
			$adapterFactory = $this->objectManager->create('FireGento\FastSimpleImport\Model\Adapters\NestedArrayAdapterFactory');
			$importerModel->setImportAdapterFactory($adapterFactory);
			$processed = $importerModel->processImport($simpleProductArr);
		  
			$logTracing = $importerModel->getLogTrace();
			$this->logger->info($logTracing);
			
			if($processed  === true){
				$Ackprocess['processed'] = 1;
				$Ackprocess['processed_at'] = date('Y-m-d H:i:s');
				$Ackprocess['id'] = $processId;   
				$model = $this->objectManager->create('Indusa\Webservices\Model\RequestQueue');
				$requestsave = $model->updateProcessQueue($Ackprocess);
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
					'categories' => $categories,    
					'product_websites' => 'base',
					'name' => isset($variantName) ? $variantName : $_product['name'],
					'weight' => $_product['weight'],              
					'min_qty' => $_variantProducts['variantReservedQty'],
					'use_config_min_qty' => 0, 	
					'manufacture_id' => $attributeArr['style'],
					'manufacture_code' => $attributeArr['config'],									
                );
						
				$product = $this->_productFactory->create();				
				$existingProductID = $product->getIdBySku($SKU);
				
				// -- check if product exists then inventory and price won't be updated			
				if(!$existingProductID){
					
					//Price updates if product does not exists
					$_simpleproducts['price'] = $_product['price'];
					$_simpleproducts['special_price'] = $_product['specialPrice'];
					               
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
					$_simpleproducts['license'] = $_product['LICENSE'];
					$_simpleproducts['gender'] = $_product['Gender'];
					$_simpleproducts['brand'] = $_product['BRAND'];  
					$_simpleproducts['video'] = isset($_product['video_url'])? $_product['video_url'] :'';
					$_simpleproducts['visibility'] = 'Catalog, Search';
				}
				
				//creating simple products
				$configProduct[] = array_merge($_simpleproducts,$attributeArr); 
				//$this->logger->info($count++);
            }
           
			if(isset($configurable_variations) && is_array($configurable_variations))
			{
				$configProduct[] = array(
				'sku' => $_product['axProductID'],
				'attribute_set_code' => 'Default',
				'product_type' => 'configurable',
				'product_websites' => 'base',
				'name' => $_product['name'],	
				'price' => 	$_product['price'],
				'special_price' => 	$_product['specialPrice'],
				'categories' => $categories,
				'description' => $_product['description'],
				'weight' => $_product['weight'],
				'is_newproduct' => $_product['new'],				
				'is_featured' =>  $_product['featured'], 
				'is_seller' =>  $_product['hotSeller'] , 
				'website_only' =>  $_product['online'], 
				'is_homedelivery' =>  $_product['onlyHD'],
				'age_group' =>   $_product['AGE'],
				'license' =>   $_product['LICENSE'],
				'gender' =>    $_product['Gender'],
				'brand' =>    $_product['BRAND'],    
				'video' =>  isset($_product['video_url'])? $_product['video_url'] :'',
				'manage_stock' => 0,   
				'use_config_manage_stock' => 0, 
				'use_config_min_qty' => 0,    				
				'configurable_variations' => $configurable_variations
			   ); 
			}
        }
		return $configProduct; 
		}
}
