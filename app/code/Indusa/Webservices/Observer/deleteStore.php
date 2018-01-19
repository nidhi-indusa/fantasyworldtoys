<?php

namespace Indusa\Webservices\Observer;

use \Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class deleteStore implements ObserverInterface {

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     */
    public function execute(EventObserver $observer) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $eventProduct = $observer->getEvent()->getProduct();

        if ($eventProduct && $eventProduct->getId()) {
            $Sku = $eventProduct->getSku();
            $inventoryStoreFactory = $objectManager->create('Indusa\Webservices\Model\InventoryStoreFactory');
            $resultFactory = $inventoryStoreFactory->create()->getCollection()->addFieldToFilter('product_sku', $Sku);
            foreach ($resultFactory as $item) {
                $item->delete();
            }
        }
    }

}
