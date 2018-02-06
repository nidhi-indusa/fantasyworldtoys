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
    protected $_stockItemRepository;

    /**
     * Constructor
     *
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
    CheckoutSession $checkoutSession, \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository, \Indusa\GoogleMapsStoreLocator\Helper\Data $helper, \Indusa\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator\CollectionFactory $collectionFactory, \Indusa\Webservices\Model\ResourceModel\InventoryStore\CollectionFactory $invetorycollectionFactory, ObjectManagerInterface $objectManager
    ) {

        $this->checkoutSession = $checkoutSession;
        $this->_stockItemRepository = $stockItemRepository;
        $this->collectionFactory = $collectionFactory;

        $this->invetorycollectionFactory = $invetorycollectionFactory;

        $this->objectManager = $objectManager;
        $this->googleMapsStoreHelper = $helper;
        $this->checkoutSession = $checkoutSession;
    }

    public function getSimpleProductData($quoteid) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $quotedata = $objectManager->create('\Magento\Quote\Model\Quote')->load($quoteid);
        $quoteItemCollection = $objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Item\Collection::class);
        $quote = $objectManager->create(\Magento\Quote\Model\Quote::class);
        //$quote->setStoreId($data['store_id']);
        $quoteItemCollection->setQuote($quote)
                ->addFieldToFilter('quote_id', $quoteid)
                ->addFieldToFilter('product_type', 'simple');
        //->addFieldToFilter('parent_item_id', array('null' => true));

        $cnt = count($quoteItemCollection->getData());

        if ($cnt > 0) {
            $simplequotedataarray = $quoteItemCollection->getData()[0];
        } else {
            $simplequotedataarray = array();
        }

        return $simplequotedataarray;
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


            //Fetch custom city and state  value start
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('city');
            $sql1 = "Select * FROM " . $tableName;
            $cityresult = $connection->fetchAll($sql1);
            $cityattributesArrays = array();
            foreach ($cityresult as $alldata) {
                $cityattributesArrays[$alldata['id']] = array(
                    'city_name' => $alldata['city_name'],
                    'state_id' => $alldata['state_id']
                );
            }

            $statetableName = $resource->getTableName('directory_country_region');
            $sqlstate = "SELECT * FROM  " . $statetableName . " where country_id = 'KW' ";
            $stateresult = $connection->fetchAll($sqlstate);


            $stateattributesArrays = array();
            foreach ($stateresult as $allstatedata) {
                $stateattributesArrays[$allstatedata['region_id']] = array(
                    'region_id' => $allstatedata['region_id'],
                    'country_id' => $allstatedata['country_id'],
                    'code' => $allstatedata['code'],
                    'default_name' => $allstatedata['default_name'],
                );
            }
            $result['cityOptionData'] = $cityattributesArrays;
            $result['stateOptionData'] = $stateattributesArrays;
            //Fetch custom city and state  value start
        }


        //Custom code added for store locator start
        $storecollection = $this->collectionFactory->create()->addFieldToFilter('is_active', 1)
                ->setOrder('creation_time', 'DESC');
        
                
        foreach ($storecollection as $index1 => $item1) {
            $result['storeItemData'][$index1] = $item1->getData();
            
            $result['customoptionsList'][$index1] =  $item1->getData();
            
            //$result['customoptionsList'][$index1]['value'] = $item1->getData('ax_storeid');
            //$result['customoptionsList'][$index1]['label'] = $item1->getData('store_name');
        }
        //Custom code added for store locator end
        $newmessage = array();

        foreach ($storecollection as $index1 => $item1) {

            foreach ($items as $index => $item) {

                $quoteItem = $this->checkoutSession->getQuote()->getItemById($item['item_id']);

                $quoteId = $item['item_id'];
                $quote = $objectManager->create('\Magento\Quote\Model\Quote\Item')->load($quoteId);
                $type = $quote->getData("product_type");
                if ($type == "configurable") {
                    $simpledata = $this->getSimpleProductData($quote->getData('quote_id'));
                    $productId = $simpledata['product_id'];
                } elseif ($type == "simple") {
                    $productId = $quote->getProductId();
                } else {
                    $productId = $quote->getProductId();
                }


                // $productId = $quote->getProductId();
                // $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');

                /* $product = $objectManager->get('Magento\Catalog\Model\Product')->load(2737);
                  $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
                  $productQty = $StockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId()); */

                /*  $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
                 */
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($productId);
                //print_r($productStockObj->getMinQty());
                $productstockqty = $productStockObj->getData('qty');

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
                            if ($quote->getData('product_type') == 'simple') {
                                $newmessage[$index][$index1] = "false";
                                $result['storeItemData'][$index1]['message_store_qty'][] = 'false';
                                $result['storeItemData'][$index1]['productcustom_message'][] = "<span class='prod-name'>" . $quote->getData('name') . "</span><span class='outstock'> (Not available in Store) </span>";

                                $storeqty_taken = $invetorycollection->getData()[0]['quantity'];
                                $result['storeItemData'][$index1]['transfer_order_quantity'][$quote->getItemId()] = 0;
                            } else {
                                $newmessage[$index][$index1] = "true";
                                $result['storeItemData'][$index1]['message_store_qty'][] = 'true';
                                $result['storeItemData'][$index1]['productcustom_message'][] = "<span class='prod-name'>" . $quote->getData('name') . "</span><span class='instock'>  (Available in Store) </span>";

                                $storeqty_taken = $invetorycollection->getData()[0]['quantity'];
                                $result['storeItemData'][$index1]['transfer_order_quantity'][$quote->getItemId()] = 0;
                            }
                        }

                        //section b.   Check if the quantity required by the user for that product is available END
                        //section b.   Available Quantity of a product = (Total quantity of that product in Warehouse + Total quantity of that product in that store) - Reserved Quantity of that product
                    }
                } else {


                    $finalavailableqty = $quote->getData('qty');

                    //if ( ($finalavailableqty)  <= $productstock->getData('qty') ) {

                    if ($quote->getData('product_type') == 'simple') {
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
                    } else {

                        $result['storeItemData'][$index1]['message_store_qty'][] = 'true';
                        $result['storeItemData'][$index1]['productcustom_message'][] = "<span class='prod-name'>" . $quote->getData('name') . "</span><span class='instock'>  (Available in Store) </span>";
                        $storeqty_taken = 0;
                        $result['storeItemData'][$index1]['transfer_order_quantity'][$quote->getItemId()] = $quote->getData('qty');
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
                $tmp = array_filter($storeitemdata['transfer_order_quantity']);
                if (empty($tmp)) {
                    $result['storeItemData'][$key]['deliverymethod_time'] = 'true';
                } else {
                    $result['storeItemData'][$key]['deliverymethod_time'] = 'false';
                }
                $result['storeItemData'][$key]['transfer_order_quantity'] = json_encode($storeitemdata['transfer_order_quantity']);
            } else {
                $result['storeItemData'][$key]['deliverymethod_time'] = 'true';
                $result['storeItemData'][$key]['transfer_order_quantity'] = $storeitemdata['transfer_order_quantity'];
            }

            //Check
            if ($result['storeItemData'][$key]['deliverymethod_time'] == 'true') {
                $result['storeItemData'][$key]['ordertime_message'] = __('Entire Order can be collected after 60 minutes');
            } else {
                $ordertime_message = $this->checkOrderTimeMessage();
                $result['storeItemData'][$key]['ordertime_message'] = __($ordertime_message);
            }

            $result['storeItemData'][$key]['delivery_from'] = 'Store';
            $result['storeItemData'][$key]['deliverymethod'] = 'clickandcollect';
            $result['storeItemData'][$key]['newdeliverymethod'] = 'clickandcollect';
        }

         /* echo "2eee2";
          echo "<pre>";
          print_r($result['storeItemData']);
          print_r($productstockqty);
          die(); */

        return $result;
    }

    public function checkOrderTimeMessage() {

        date_default_timezone_set('Asia/Kuwait');
        $date_currentdate_time = time();

        //$OnePm = mktime(13, 0, 0); //One PM
        $OnePm = mktime(13); //One PM
        $SixPm = mktime(18); //Six PM
        $EightPm = mktime(20); // Eight PM

        if ($date_currentdate_time <= $OnePm) {
            $ordertime_message = __('Entire Order can be collected at 3:00 PM today');
        } else if ($date_currentdate_time > $OnePm && $date_currentdate_time <= $SixPm) {
            $ordertime_message = __('Entire Order can be collected at 8:00 PM today');
        } else if ($date_currentdate_time > $SixPm) {
            $ordertime_message = __('Entire Order can be collected at 10:00 AM tomorrow');
        }else{
            $ordertime_message ="";
        }

       // echo  $date_currentdate_time.":::".$EightPm.":::message=".$ordertime_message;die();
        return $ordertime_message;
    }
}
