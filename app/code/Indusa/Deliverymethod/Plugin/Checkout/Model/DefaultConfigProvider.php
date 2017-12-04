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

namespace Indusa\Deliverymethod\Plugin\Checkout\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\ObjectManagerInterface;

class DefaultConfigProvider {

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    protected $collectionFactory;
    protected $invetorycollectionFactory;
    protected $objectManager;
    public $googleMapsStoreHelper;

    /**
     * Constructor
     *
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
    CheckoutSession $checkoutSession, \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository, \Indusa\GoogleMapsStoreLocator\Helper\Data $helper, \Indusa\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator\CollectionFactory $collectionFactory, \Indusa\Webservices\Model\ResourceModel\InventoryStore\CollectionFactory $invetorycollectionFactory, ObjectManagerInterface $objectManager
    ) {
        
        $this->checkoutSession = $checkoutSession;
        $this->collectionFactory = $collectionFactory;

        $this->invetorycollectionFactory = $invetorycollectionFactory;

        $this->objectManager = $objectManager;
        $this->googleMapsStoreHelper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->_stockItemRepository = $stockItemRepository;
    }

   

    public function afterGetConfig(
    \Magento\Checkout\Model\DefaultConfigProvider $subject, array $result
    ) {
        $items = $result['totalsData']['items'];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        foreach ($items as $index => $item) {
           
            $quoteItem = $this->checkoutSession->getQuote()->getItemById($item['item_id']);
            
            //
            $quoteId = $item['item_id'];
            $quote = $objectManager->create('\Magento\Quote\Model\Quote\Item')->load($quoteId);
            
            $newquoteid = $quote->getQuoteId();
            $quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');
            $quotealldata = $quoteFactory->create()->load($newquoteid);
           

            $productId = $quote->getProductId();
            $product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);
            $productis_homedelivery = $product->getResource()->getAttribute('is_homedelivery')->getFrontend()->getValue($product);
            //
            
           
           // echo $quoteItem->getCustomMessage();echo  "<br>";
           // die();
        
            $result['quoteItemData'][$index]['is_homedelivery'] = $productis_homedelivery;
            $result['quoteItemData'][$index]['manufacturer'] = $quoteItem->getProduct()->getAttributeText('manufacturer');
            $result['quoteItemData'][$index]['custom_message'] = $quotealldata->getCustomMessage();
        }
        
        
        //Custom code added for store locator start
        $storecollection = $this->collectionFactory->create()->addFieldToFilter('is_active', 1)
                ->setOrder('creation_time', 'DESC');
        foreach ($storecollection as $index1 => $item1) {
            $result['storeItemData'][$index1] = $item1->getData();
        }
        //Custom code added for store locator end
        $newmessage = array();

        foreach ($storecollection as $index1 => $item1) {

            foreach ($items as $index => $item) {

                $quoteItem = $this->checkoutSession->getQuote()->getItemById($item['item_id']);
               
                $quoteId = $item['item_id'];
                $quote = $objectManager->create('\Magento\Quote\Model\Quote\Item')->load($quoteId);
                
                
                $productId = $quote->getProductId();
                $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
                $productstockqty = $StockState->getStockQty($productId);

                $product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);

                $productis_homedelivery = $product->getResource()->getAttribute('is_homedelivery')->getFrontend()->getValue($product);


                $result['advancedquoteItemData'][$index]['product_id'] = $quote->getProductId();

                $result['advancedquoteItemData'][$index]['name'] = $quote->getData('name');
                $result['advancedquoteItemData'][$index]['sku'] = $quote->getData('sku');

                $result['advancedquoteItemData'][$index]['qty'] = $quote->getData('qty');
              
                $result['advancedquoteItemData'][$index]['warehouse_qty'] = $productstockqty;

                $result['advancedquoteItemData'][$index]['is_homedelivery'] = $productis_homedelivery;
                $result['advancedquoteItemData'][$index]['manufacturer'] = $quoteItem->getProduct()->getAttributeText('manufacturer');
                $ax_storeid = $item1->getData('ax_storeid');

                //Custom code for fetching table from catalog_product_store_inventory store qty start
           
                $inventoryStoreFactory = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
                $invetorycollection = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('ax_store_id', $ax_storeid)->addFieldToFilter('product_sku', $quote->getData('sku'));
                $invetorycnt = count($invetorycollection->getData());

                if ($invetorycnt > 0) {
                    //$showHide['advancedquoteItemData'][$index]['store_qty'][$ax_storeid] = $invetorycollection->getData()[0]['quantity'];
                    /////section a.Check if the quantity required by the user for that product is available in the store chosen by the end user start
                    /////section a. Available Quantity of a product = Total quantity of that product in that store - Reserved Quantity of that product
                   
                    if ($invetorycollection->getData()[0]['quantity'] >= $quote->getData('qty')) {
                        $result['advancedquoteItemData'][$index]['store_qty'][$ax_storeid] = "true";
                        $newmessage[$index][$index1] = "true";
                        $result['storeItemData'][$index1]['message_store_qty'][] = 'true';



                        $result['storeItemData'][$index1]['transfer_order_quantity'][] = 0;
                        $result['storeItemData'][$index1]['productcustom_message'][] = "<span class='prod-name'>" . $quote->getData('name') . "</span><span class='instock'>  (Available in Store) </span>";
                      
                    } else {
                     

                        $finalavailableqty = $invetorycollection->getData()[0]['quantity'] + $productstockqty;
                  
                        if (($finalavailableqty) >= $quote->getData('qty')) {
                            $newmessage[$index][$index1] = "true";
                            $result['storeItemData'][$index1]['message_store_qty'][] = 'true';
                            $result['storeItemData'][$index1]['productcustom_message'][] = "<span class='prod-name'>" . $quote->getData('name') . "</span><span class='instock'>  (Available in Store) </span>";
                            $storeqty_taken = $invetorycollection->getData()[0]['quantity'];

                          
                            $amnt = $quote->getData('qty') - $invetorycollection->getData()[0]['quantity'];
                            $amnt = number_format($amnt, 4);

                            $result['storeItemData'][$index1]['transfer_order_quantity'][$quote->getItemId()] = $amnt;
                        } else {
                            $newmessage[$index][$index1] = "false";
                            $result['storeItemData'][$index1]['message_store_qty'][] = 'false';
                            $result['storeItemData'][$index1]['productcustom_message'][] = "<span class='prod-name'>" . $quote->getData('name') . "</span><span class='outstock'> (Not available in Store) </span>";

                            $storeqty_taken = $invetorycollection->getData()[0]['quantity'];
                            $result['storeItemData'][$index1]['transfer_order_quantity'][$quote->getItemId()] = 0;
                        }
                        //section b.   Check if the quantity required by the user for that product is available END
                        //section b.   Available Quantity of a product = (Total quantity of that product in Warehouse + Total quantity of that product in that store) - Reserved Quantity of that product
                    }
                } else {

                  
                    $finalavailableqty = $quote->getData('qty');

                    //if ( ($finalavailableqty)  <= $productstock->getData('qty') ) {
                    if ($productstockqty >= $quote->getData('qty')) {
                        $newmessage[$index][$index1] = "true";
                        $result['storeItemData'][$index1]['message_store_qty'][] = 'true';
                        $result['storeItemData'][$index1]['productcustom_message'][] = "<span class='prod-name'>" . $quote->getData('name') . "</span><span class='instock'>  (Available in Store) </span>";
                        $storeqty_taken = 0;
                        $result['storeItemData'][$index1]['transfer_order_quantity'][$quote->getItemId()] = $quote->getData('qty');
                    } else {
                        $newmessage[$index][$index1] = "false";
                        $result['storeItemData'][$index1]['message_store_qty'][] = 'false';
                        $result['storeItemData'][$index1]['productcustom_message'][] = "<span class='prod-name'>" . $quote->getData('name') . "</span><span class='outstock'> (Not available in Store) </span>";
                        $storeqty_taken = 0;
                        $result['storeItemData'][$index1]['transfer_order_quantity'][$quote->getItemId()] = 0;
                    }
                    //section b.   Check if the quantity required by the user for that product is available END
                    //section b.   Available Quantity of a product = (Total quantity of that product in Warehouse + Total quantity of that product in that store) - Reserved Quantity of that product
                }
                /////section a.Check if the quantity required by the user for that product is available in the store chosen by the end user  end
                /////section a. Available Quantity of a product = Total quantity of that product in that store - Reserved Quantity of that product
                //Custom code for fetching table from catalog_product_store_inventory store qty end
            }
        }
    

        foreach ($result['storeItemData'] as $key => $storeitemdata) {



            $result['storeItemData'][$key]['productcustom_message'] = "<p class='xxx'>" . implode("</p><p class='yyy'>", $storeitemdata['productcustom_message']) . "</p>";




            if (in_array("false", $storeitemdata['message_store_qty'])) {
                $result['storeItemData'][$key]['message_store_qty'] = 'false';
            } else {
                $result['storeItemData'][$key]['message_store_qty'] = 'true';
            }


            
            if ($storeitemdata['transfer_order_quantity'] != 0) {
                $result['storeItemData'][$key]['transfer_order_quantity'] = json_encode($storeitemdata['transfer_order_quantity']);
            } else {
                $result['storeItemData'][$key]['transfer_order_quantity'] = $storeitemdata['transfer_order_quantity'];
            }
            
            $result['storeItemData'][$key]['delivery_from'] = 'Store';
            $result['storeItemData'][$key]['deliverymethod'] = 'clickandcollect';
            $result['storeItemData'][$key]['newdeliverymethod'] = 'clickandcollect';
            
            $result['storeItemData'][$key]['newdeliverymethod'] = 'clickandcollect';
            
        }

     
        
        
        return $result;
    }

}
