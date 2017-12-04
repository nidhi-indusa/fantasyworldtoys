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

namespace Indusa\Deliverymethod\Model;

use Magento\Framework\Exception\CouldNotSaveException;

class PaymentInformationManagement extends \Magento\Checkout\Model\PaymentInformationManagement {

    public function savePaymentInformationAndPlaceOrder(
    $cartId, \Magento\Quote\Api\Data\PaymentInterface $paymentMethod, \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $quote = $this->quoteRepository->getActive($cartId);

        //Check for delivery_method seleceted clickandcollect and delivery_from store start
        if ($quote->getDeliveryMethod() == 'clickandcollect' && $quote->getDeliveryFrom() == 'Store') {


            $quote = $this->quoteRepository->get($quote->getEntityId());
            $items = $quote->getAllItems();

            foreach ($items as $index => $item) {
                $quote = $objectManager->create('\Magento\Quote\Model\Quote')->load($item->getQuoteId());
                $requestedQty = $item->getQty();
                $itemQty = $item->getQty();
                $productId = $item->getProductId();
                $product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);
                $productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($productId);
                $ax_storeid = $quote->getAxStoreId();
                $inventoryStoreFactory = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
                $resultFactory = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('ax_store_id', $ax_storeid)->addFieldToFilter('product_sku', $product->getData('sku'))->getFirstItem();
                if ($resultFactory->getId() > 0) {

                    $storeqty = number_format($resultFactory->getQuantity(), 4);
                    $requestedQty = number_format($item->getQty(), 4);
                    $response = $this->checkStoreAvaibilty($productStockObj->getData('qty'), $storeqty, $requestedQty);
                } else {
                    $storeqty = 0;
                    $response = $this->checkStoreAvaibilty($productStockObj->getData('qty'), $storeqty, $requestedQty);
                }
            }

            if ($response == 0) {

                throw new CouldNotSaveException(__('Unable to proceed further due to Selected Click and Collect Method stock not available.'));
                return false;
            } else {
                $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
                try {
                    $orderId = $this->cartManagement->placeOrder($cartId);


                    $orderData = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);
                    $orderItems = $orderData->getAllItems();
                    foreach ($orderItems as $index => $item) {
                        $quote = $objectManager->create('\Magento\Quote\Model\Quote\Item')->load($item->getQuoteItemId());
                        $requestedQty = $quote->getQty();
                        $itemQty = $quote->getQty();
                        $productId = $quote->getProductId();
                        $product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);
                        $productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($productId);
                        $ax_storeid = $quote->getAxStoreId();
                        $inventoryStoreFactory = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
                        $resultFactory = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('ax_store_id', $ax_storeid)->addFieldToFilter('product_sku', $product->getData('sku'))->getFirstItem();
                        if ($resultFactory->getId() > 0) {
                            if ($response == 1) {
                                $stockRegistry = $objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface');
                                $stockItem = $stockRegistry->getStockItem($productId);

                                $storeqty = number_format($resultFactory->getQuantity(), 4);

                                if ($storeqty > $requestedQty) {
                                    $newqty = $productStockObj->getData('qty') + $itemQty;
                                    if ($newqty > 0)
                                        $is_in_stock = 1;
                                    else
                                        $is_in_stock = 0;

                                    $stockItem->setData('qty', $newqty);
                                    $stockItem->setData('is_in_stock', $is_in_stock);
                                    $stockRegistry->updateStockItemBySku($product->getData('sku'), $stockItem);
                                }
                                else {
                                    $newqty = $productStockObj->getData('qty') + $resultFactory->getQuantity();

                                    if ($newqty > 0)
                                        $is_in_stock = 1;
                                    else
                                        $is_in_stock = 0;

                                    $stockItem->setData('qty', $newqty);
                                    $stockItem->setData('is_in_stock', $is_in_stock);
                                    $stockRegistry->updateStockItemBySku($product->getData('sku'), $stockItem);
                                }

                                if ($resultFactory->getId() > 0) {
                                    if ($storeqty < $requestedQty) {
                                        $storelatestqty = 0;
                                    } else {
                                        $storelatestqty = $storeqty - $requestedQty;
                                    }
                                    $model = $objectManager->create('Indusa\Webservices\Model\InventoryStore');
                                    $data = array('id' => $resultFactory->getId(), 'ax_store_id' => $resultFactory->getAxStoreId(), 'product_sku' => $product->getData('sku'), 'quantity' => $storelatestqty);
                                    $model->setData($data);

                                    $model->save();
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    throw new CouldNotSaveException(
                    __('An error occurred on the server. Please try to place the order again.'), $e
                    );
                }
                //die();
                return $orderId;
            }
        } else if ($quote->getDeliveryMethod() == 'homedelivery' && $quote->getDeliveryFrom() == 'Warehouse') {

            $deliveryDate = $quote->getDeliveryDate();

            if ($deliveryDate != '') {
                $canBeDelivered = 0;
                $canBeDelivered = $this->getCheckDeliveryDateStatus($deliveryDate);
            }

            if (!$canBeDelivered) {
                //If Error Stay on same screen
                //$msg = "Your order cannot be shipped on date  " . $deliveryDate . " chosen, please choose another date from Shipping page!";
                $msg = "Sorry, your order cannot be shipped on date chosen. Please do choose another date !";
                throw new CouldNotSaveException(__($msg));
            }


            $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
            try {

                $orderId = $this->cartManagement->placeOrder($cartId);
            } catch (\Exception $e) {
                throw new CouldNotSaveException(
                __('An error occurred on the server. Please try to place the order again.'), $e
                );
            }
            return $orderId;
        } else {


            $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
            try {
                $orderId = $this->cartManagement->placeOrder($cartId);
            } catch (\Exception $e) {
                throw new CouldNotSaveException(
                __('An error occurred on the server. Please try to place the order again.'), $e
                );
            }
            return $orderId;
        }

        // die();
        //Check for delivery_method seleceted clickandcollect and delivery_from store end
    }

    public function checkStoreAvaibilty($productstockqty, $storeqty, $requestedQty) {

        if ($storeqty > $requestedQty) {
            $totqty = $storeqty;
        } else {
            $totqty = $storeqty + $productstockqty;
        }
        if ($totqty >= $requestedQty) {
            return 1;
        } else {
            return 0;
        }
    }

    public function getStockItem($productId) {
        return $this->_stockItemRepository->get($productId);
    }

    public function getCheckDeliveryDateStatus($date) {

        static $canBeDelivered = 0;
        static $countOfOrders = 0;
        // $deliverydatedata = $this->deliverydateconfigprovider->getConfig();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $maxorders = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('indusa_deliverydatemethod/general/maxorders');

        //countOfOrders = check in WEB DB about all Orders where deliveryDate= DELIVERYDATE AND STATUS != COMPLETED && DELIVERYTYPE = HOMEDELIVERY
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $allorder = $objectManager->create('\Magento\Sales\Model\Order')->getCollection()
                //->addFieldToFilter('status', 'pending')
                ->addFieldToFilter('status', array('neq' => 'complete'))
                ->addFieldToFilter('delivery_method', 'homedelivery')
                ->addFieldToFilter('delivery_date', array('eq' => $date));

        $countOfOrders = count($allorder);
        //        echo "ordercount==".$countOfOrders;echo  "<br>";
        //        echo "maxorders==".$maxorders;echo  "<br>";
        //        die();
        if ($countOfOrders < $maxorders) {
            $canBeDelivered = 1;
            //$this->_configWriter->save('indusa_deliverydatemethod/general/show_hide_canBeDelivered', 1, 'default', 0);
        } else {
            $canBeDelivered = 0;
            //$this->_configWriter->save('indusa_deliverydatemethod/general/show_hide_canBeDelivered', 0, 'default', 0);
        }
        return $canBeDelivered;
    }

    public function CheckBestDeliveryDate($date) {

        $status = false;
        $i = 0;
        while ($status === false) {
            //$deliverydatedata = $this->deliverydateconfigprovider->getConfig();
            $maxorders = 1; //$deliverydatedata['shipping']['deliverydatemethod']['maxOrders'];
            //countOfOrders = check in WEB DB about all Orders where deliveryDate= DELIVERYDATE AND STATUS != COMPLETED && DELIVERYTYPE = HOMEDELIVERY
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $allorder = $objectManager->create('\Magento\Sales\Model\Order')->getCollection()
                    //->addFieldToFilter('status', 'pending')
                    ->addFieldToFilter('status', array('neq' => 'complete'))
                    ->addFieldToFilter('delivery_method', 'homedelivery')
                    ->addFieldToFilter('delivery_date', array('eq' => $date));
            $countOfOrders = count($allorder);

            if ($countOfOrders < $maxorders) {
                $canBeDelivered = 1;
                $status = true;
                $this->_configWriter->save('indusa_deliverydatemethod/general/show_hide_canBeDelivered', 1, 'default', 0);
            } else {
                $i++;
                $date = date('Y-m-d', strtotime($date . ' +' . $i . ' day'));
                $status = false;
                $this->_configWriter->save('indusa_deliverydatemethod/general/show_hide_canBeDelivered', 0, 'default', 0);
                //echo $date.">>>>>>>>>>>>>>";echo  "<br>";
                return $this->CheckBestDeliveryDate($date);
            }
        }
        return $date;
    }

}
