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

    /**
     * @param Magento\Framework\App\ObjectManagerFactory $objectManagerFactory
     * 
     */
    public function __construct(ObjectManagerFactory $objectManagerFactory, \Indusa\Webservices\Logger\Logger $loggerInterface ) {
        $this->objectManagerFactory = $objectManagerFactory;
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
			$importerModel->processImport($simpleProductArr);
		  
		    $this->logger->info('processsing234');
			$logTracing = $importerModel->getLogTrace();
			$this->logger->info($logTracing);
		
			$Ackprocess['processed'] = 1;
			$Ackprocess['processed_at'] = date('Y-m-d H:i:s');
			$Ackprocess['id'] = $processId;                     
			   
			$model = $this->objectManager->create('Indusa\Webservices\Model\RequestQueue');
			$requestsave = $model->updateProcessQueue($Ackprocess);
      
			return true;
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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$AttributeModel = $objectManager->create('\Indusa\Webservices\Model\Attribute');
        $InventoryModel = $objectManager->create('\Indusa\Webservices\Model\InventoryStore');
        $products = [];
        $CategoryModel = $objectManager->create('\Indusa\Webservices\Model\Service');
     
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
            $Simpleproducts = [];  
		   
            //===================create simple product==========================//
            $variants = $_product['variants']['variant'];
            if(array_key_exists(0,$variants)){ $variantProducts = $variants; } else { $variantProducts[] =  $variants; }
            $catIds =  $_product['productCategories']['productCategory'];
            $categories = $CategoryModel->getCategory($catIds);
			foreach($variantProducts as $_variantProducts)  
			{				
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
				
				$configurableFlag = 0;
				if(isset($attributeArr['size'])){ $SKU .= '-'.$attributeArr['size']; $configurableFlag =1; }
				if(isset($attributeArr['color'])){ $SKU .= '-'.$attributeArr['color']; $configurableFlag =1;}
					
				if(isset($configurableFlag)) { $_variantProducts['variantName'] = $_variantProducts['variantName']."-".$attributeArr['size']."-".$attributeArr['color']; }
								
                $_simpleproducts = array(
                'sku' => $SKU,   
				'variant_sku' => $_variantProducts['variantSKU'],
                'attribute_set_code' => 'Default',
                'product_type' => 'simple',
                'categories' => $categories,    
                'product_websites' => 'base',
                'name' => $_variantProducts['variantName'],
                'weight' => preg_replace("/[^0-9]/","",$_product['weight']),
                'price' => preg_replace("/[^0-9]/","",$_product['price']), 
                'special_price' => preg_replace("/[^0-9]/","",$_product['specialPrice']),
                'min_qty' => $_variantProducts['variantReservedQty'],
                'use_config_min_qty' => 0, 
				'url_key' => $_variantProducts['variantName'],
                );   
               
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
                $_simpleproducts['qty'] = $simpleProductqty;           
               
                $configProduct[] = array_merge($_simpleproducts,$attributeArr); 
                
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
                }
            }
           
			if(isset($configurable_variations) && is_array($configurable_variations))
			{
				$configProduct[] = array(
				'sku' => $_product['axProductID'],
				'attribute_set_code' => 'Default',
				'product_type' => 'configurable',
				'product_websites' => 'base',
				'name' => $_product['name'],
				'categories' => $categories,
				'description' => $_product['description'],
				'weight' => preg_replace("/[^0-9]/","",$_product['weight']),    
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
				'url_key' => $_product['name'],
				'configurable_variations' => $configurable_variations
			   ); 
			}
        }
		 $this->logger->info(json_encode($configProduct));
		 
		return $configProduct; 
		}
}
