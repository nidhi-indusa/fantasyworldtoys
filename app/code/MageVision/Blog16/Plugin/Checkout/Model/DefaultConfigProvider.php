<?php

/**
 * MageVision Blog16
 *
 * @category     MageVision
 * @package      MageVision_Blog16
 * @author       MageVision Team
 * @copyright    Copyright (c) 2017 MageVision (https://www.magevision.com)
 * @license      http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace MageVision\Blog16\Plugin\Checkout\Model;

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
    CheckoutSession $checkoutSession, \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository, \FME\GoogleMapsStoreLocator\Helper\Data $helper, \FME\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator\CollectionFactory $collectionFactory, \Indusa\Webservices\Model\ResourceModel\InventoryStore\CollectionFactory $invetorycollectionFactory, ObjectManagerInterface $objectManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->collectionFactory = $collectionFactory;

        $this->invetorycollectionFactory = $invetorycollectionFactory;

        $this->objectManager = $objectManager;
        $this->googleMapsStoreHelper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->_stockItemRepository = $stockItemRepository;
    }

    public function getStockItem($productId) {
        return $this->_stockItemRepository->get($productId);
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
            $productId = $quote->getProductId();
            $product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);
            $productis_homedelivery = $product->getResource()->getAttribute('is_homedelivery')->getFrontend()->getValue($product);
            //


            $result['quoteItemData'][$index]['is_homedelivery'] = $productis_homedelivery;
            $result['quoteItemData'][$index]['manufacturer'] = $quoteItem->getProduct()->getAttributeText('manufacturer');
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
//                echo  "item ...";
//                echo "<pre>";
//                print_r($item);
                $quoteItem = $this->checkoutSession->getQuote()->getItemById($item['item_id']);
                //
                $quoteId = $item['item_id'];
                $quote = $objectManager->create('\Magento\Quote\Model\Quote\Item')->load($quoteId);
                
                
              
                
                
                $productId = $quote->getProductId();
                $productstock = $this->getStockItem($productId);

                $product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);

                $productis_homedelivery = $product->getResource()->getAttribute('is_homedelivery')->getFrontend()->getValue($product);
                //
//                echo "<pre>";
//                echo  "qui";
//                print_r($quote->getData());

                $result['advancedquoteItemData'][$index]['product_id'] = $quote->getProductId();

                $result['advancedquoteItemData'][$index]['name'] = $quote->getData('name');
                $result['advancedquoteItemData'][$index]['sku'] = $quote->getData('sku');

                $result['advancedquoteItemData'][$index]['qty'] = $quote->getData('qty');
                //echo "<pre>";
                //print_r($item);
                //echo  "product...";
                //print_r($product->getData());
                //die();
                $result['advancedquoteItemData'][$index]['warehouse_qty'] = $productstock->getData('qty');

                $result['advancedquoteItemData'][$index]['is_homedelivery'] = $productis_homedelivery;
                $result['advancedquoteItemData'][$index]['manufacturer'] = $quoteItem->getProduct()->getAttributeText('manufacturer');
                $ax_storeid = $item1->getData('ax_storeid');

                //Custom code for fetching table from catalog_product_store_inventory store qty start
                //$invetorycollection = $this->invetorycollectionFactory->create()->addFieldToFilter('product_sku', $product->getData('sku'))->addFieldToFilter('ax_store_id', $ax_storeid);
                //$invetorycnt = count($invetorycollection->getData());
                $inventoryStoreFactory = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
                $invetorycollection = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('ax_store_id', $ax_storeid)->addFieldToFilter('product_sku', $quote->getData('sku'));
                $invetorycnt = count($invetorycollection->getData());

                if ($invetorycnt > 0) {
                    //$showHide['advancedquoteItemData'][$index]['store_qty'][$ax_storeid] = $invetorycollection->getData()[0]['quantity'];
                    /////section a.Check if the quantity required by the user for that product is available in the store chosen by the end user start
                    /////section a. Available Quantity of a product = Total quantity of that product in that store - Reserved Quantity of that product
                    //if ($quote->getData('qty') <= $invetorycollection->getData()[0]['quantity']) {
                    if ($invetorycollection->getData()[0]['quantity'] >= $quote->getData('qty')) {
                        $result['advancedquoteItemData'][$index]['store_qty'][$ax_storeid] = "true";
                        $newmessage[$index][$index1] = "true";
                        $result['storeItemData'][$index1]['message_store_qty'][] = 'true';



                        $result['storeItemData'][$index1]['transfer_order_quantity'][] = 0;
                        $result['storeItemData'][$index1]['productcustom_message'][] = "<span class='prod-name'>" . $quote->getData('name') . "</span><span class='instock'>  (Available in Store) </span>";
                        // $result['storeItemData'][$index1]['productcustom_message'][$quote->getData('name')]  = "Store Instock"; 
                    } else {
                        //$newmessage[$index][$index1] = "false";
                        //$result['storeItemData'][$index1]['message_store_qty'][] = 'false';
                        //$result['storeItemData'][$index1]['productcustom_message'][] =  "<span class='prod-name'>".$quote->getData('name')."</span><span class='outstock'> (Not available in Store) </span>";
                        //$result['storeItemData'][$index1]['productcustom_message'][$quote->getData('name')]  = "Store Outofstock"; 
                        //section b.   Check if the quantity required by the user for that product is available START
                        //section b.   Available Quantity of a product = (Total quantity of that product in Warehouse + Total quantity of that product in that store) - Reserved Quantity of that product
                        // echo "quoteqty:::".$quote->getData('qty')."++++++++"."WAREHOSE:::".$productstock->getData('qty');
                        //Check with  (store qty + warehouse qty ) > order qty 

                        $finalavailableqty = $invetorycollection->getData()[0]['quantity'] + $productstock->getData('qty');
                        //$finalavailableqty  = $quote->getData('qty') - $invetorycollection->getData()[0]['quantity'];
                        // if ( ($finalavailableqty)  <= $productstock->getData('qty') ) {
                        if (($finalavailableqty) >= $quote->getData('qty')) {
                            $newmessage[$index][$index1] = "true";
                            $result['storeItemData'][$index1]['message_store_qty'][] = 'true';
                            $result['storeItemData'][$index1]['productcustom_message'][] = "<span class='prod-name'>" . $quote->getData('name') . "</span><span class='instock'>  (Available in Store) </span>";
                            $storeqty_taken = $invetorycollection->getData()[0]['quantity'];

                            // $result['storeItemData'][$index1]['transfer_order_quantity'][] = $quote->getData('qty') - $invetorycollection->getData()[0]['quantity'];
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

                    //$result['advancedquoteItemData'][$index]['store_qty'][$ax_storeid] = "false";
                    //$newmessage[$index][$index1] = "false";
                    //$result['storeItemData'][$index1]['message_store_qty'][] = 'false';
                    //$result['storeItemData'][$index1]['productcustom_message'][] =  "<span class='prod-name'>".$quote->getData('name')."</span><span class='outstock'> (Not available in Store) </span> ";
                    //$result['storeItemData'][$index1]['productcustom_message'][$quote->getData('name')]  = "Store Outofstock"; 
                    //section b.   Check if the quantity required by the user for that product is available START
                    //section b.   Available Quantity of a product = (Total quantity of that product in Warehouse + Total quantity of that product in that store) - Reserved Quantity of that product
                    //Check with  (store qty + warehouse qty ) >=  requested qty 
                    $finalavailableqty = $quote->getData('qty');

                    //if ( ($finalavailableqty)  <= $productstock->getData('qty') ) {
                    if ($productstock->getData('qty') >= $quote->getData('qty')) {
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
    
//         echo "before========";echo "<br>";
//         echo "<pre>";
//         print_r($result['storeItemData']); 
        foreach ($result['storeItemData'] as $key => $storeitemdata) {


//            $storeitemdata['message_store_qty'] = array(0=>'true',1=>'false',2=>'true');
//            echo  "key::".$key;echo  "<br>";
//            echo "<pre>";
//            print_r($storeitemdata['message_store_qty']);echo  "<br>";
            // 
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
            
        }

//        echo $result['storeItemData'][$key]['message_store_qty']; 
//        echo "after========";echo "<br>";
//        print_r($result['storeItemData']);
//        die();
        return $result;
    }

}
