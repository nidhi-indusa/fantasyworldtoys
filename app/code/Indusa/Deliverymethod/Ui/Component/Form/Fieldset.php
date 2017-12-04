<?php
/**
 * Indusa Deliverymethod
 *
 * @category     Indusa_Deliverymethod
 * @package      Indusa_Deliverymethod
 * @author      Indusa_Deliverymethod Team
 * @copyright    Copyright (c) 2017 Indusa Deliverymethod (http://www.indusa.com/)
 * @license      http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Indusa\Deliverymethod\Ui\Component\Form;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\Form\FieldFactory;
use Magento\Ui\Component\Form\Fieldset as BaseFieldset;
use Indusa\Webservices\Model\InventoryStoreFactory;

class Fieldset extends BaseFieldset {

    /**
     * @var FieldFactory
     */
    private $fieldFactory;
    protected $request;
    protected $collectionFactory;

    public function __construct(
    \Magento\Framework\App\Request\Http $request, \Indusa\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator\CollectionFactory $collectionFactory, ContextInterface $context, array $components = [], array $data = [], FieldFactory $fieldFactory) {
        parent::__construct($context, $components, $data);
        $this->fieldFactory = $fieldFactory;
        $this->request = $request;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get components
     *
     * @return UiComponentInterface[]
     */
    public function getChildComponents() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productid = $this->request->getParam('id');
        $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productid);
        $inventoryStoreFactory = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
        $resultFactory = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('product_sku', $product->getSku());



        //Custom code added for store locator start
        $storecollection = $this->collectionFactory->create()->addFieldToFilter('is_active', 1)->setOrder('creation_time', 'ASC');
        foreach ($storecollection as $strdata) {
            $AXstorearray[] = $strdata->getData('ax_storeid');
			 $newarray[$strdata->getData('ax_storeid')] = $strdata->getData('store_name');
        }
        //Custom code added for store locator end
        $AXstorearray1 = array_fill_keys($AXstorearray, 0);
        $cnt = count($resultFactory->getData());
        $newarray1 = array();
        if ($cnt > 0) {

            $found = null;
            foreach ($resultFactory->getData() as $dat) {
                if (in_array($dat['ax_store_id'], $AXstorearray)) {
                    $found[$dat['ax_store_id']] = true;
                    $newarray1[$dat['ax_store_id']] = $dat['quantity'];
                }
            }
            $store_wise_quantity = array_replace_recursive($AXstorearray1, $newarray1);
        } else {
            $store_wise_quantity = $AXstorearray1;
        }
        foreach ($store_wise_quantity as $key => $val) {
            $fields[] = array('label' =>  $newarray[$key], 'value' => $val, 'formElement' => 'input');
        }


        foreach ($fields as $k => $fieldConfig) {
            $fieldInstance = $this->fieldFactory->create();
            $name = 'my_dynamic_field_' . $k;

            $fieldInstance->setData(
                    [
                        'config' => $fieldConfig,
                        'name' => $name
                    ]
            );

            $fieldInstance->prepare();
            $this->addComponent($name, $fieldInstance);
        }

        return parent::getChildComponents();
    }

}
