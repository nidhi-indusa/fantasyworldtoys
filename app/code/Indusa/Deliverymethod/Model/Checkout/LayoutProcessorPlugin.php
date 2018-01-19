<?php

namespace Indusa\Deliverymethod\Model\Checkout;

class LayoutProcessorPlugin
{

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    
    
     public function citytoOptionArray()
    {
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
      $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
    $connection = $resource->getConnection();
    $tableName = $resource->getTableName('city');

    //Select Data from table
    $sql = "Select * FROM " . $tableName;
    $cityresult = $connection->fetchAll($sql); 
         
        /*$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cityFactory = $objectManager->create('Indusa\AddressValidator\Model\ResourceModel\City');
        $cityFactory = $cityFactory->create()->getCollection();*/
      
//         $attributesArrays = array();
//         foreach ($cityresult as $alldata){
//          
//           $attributesArrays[$alldata['id']] =array(
//                'label' => $alldata['city_name'],
//                'value' => $alldata['city_name']
//              //  'value' => $alldata['state_id']
//            );
//        }
         $attributesArrays = array();
        $attributesArrays = array('label'=> 'Please Select City' ,'value'=> '');
        
        
        
       return $attributesArrays;

    }
    
     
    
    
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array  $jsLayout
    ) {
         $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['city'] = [
        'component' => 'Magento_Ui/js/form/element/select',
        'config' => [
            'customScope' => 'shippingAddress',
            'template' => 'ui/form/field',
            'elementTmpl' => 'ui/form/element/select',
            'name' => __('City new'),

        ],
		'class' => 'unicode-bidi-brackets',
        'dataScope' => 'shippingAddress.city',
        'label' => __('City (Please Select State first)'),
        'provider' => 'checkoutProvider',
        'visible' => true,
        'validation' => [],
        'sortOrder' => 100,
        'name' => __('CITY'),
        'validation' => [
            'required-entry' => true,
        ],
        'options' => $this->citytoOptionArray(),



    ];
         
          return $jsLayout;
    
    
        
    }
}