<?php
namespace Indusa\Webservices\Model;

use Magento\Framework\App\ObjectManager;


class Attribute extends \Magento\Framework\Model\AbstractModel
{
    protected $_storeManager;
    protected $categoryRepository;    
    
    public $_objectManager;
    public $_eavConfig;    
    
    public $_attributeRepository;
    public $_resourceModel;
	
    /**
     * Initialize resource model
     *
     * @return void
    */
	
    protected function _construct()
    {
        $objectManager = ObjectManager::getInstance(); 
        $this->_objectManager = $objectManager;      
    }
   
    public function mappingAttribute($attributeArr = array())
    {
        if(count($attributeArr) < 0)
        {
            return;
        }
        $attributeCreatedArr = array();
    
        foreach($attributeArr  as $_attribute)
        {
            $attr_code = strtolower($_attribute['variantAttributeName']);
            $attr_value = $_attribute['variantAttributeValue'];
           
            if(!$attr_value || is_array($attr_value)) continue;          
            $attributeCreatedArr[$attr_code] =  $attr_value;            
        }        
            return $attributeCreatedArr;
    }  
}
?>