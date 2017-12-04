<?php
namespace FME\GoogleMapsStoreLocator\Block;
 
use Magento\Framework\View\Element\Template;
use Magento\Framework\ObjectManagerInterface;

class Storelocator extends Template
{
          
    protected $scopeConfig;
    protected $collectionFactory;
    protected $objectManager;
    public $googleMapsStoreHelper;
        
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \FME\GoogleMapsStoreLocator\Helper\Data $helper,
        \FME\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator\CollectionFactory $collectionFactory,
        ObjectManagerInterface $objectManager
    ) {
        
        $this->collectionFactory = $collectionFactory;
        $this->objectManager = $objectManager;
        $this->googleMapsStoreHelper = $helper;
        parent::__construct($context);
    }
    public function _prepareLayout()
    {
        if ($this->googleMapsStoreHelper->isEnabledInFrontend()) {
            $this->pageConfig->setKeywords($this->googleMapsStoreHelper->getGMapMetaKeywords());
            $this->pageConfig->setDescription($this->googleMapsStoreHelper->getGMapMetadescription());
            $this->pageConfig->getTitle()->set($this->googleMapsStoreHelper->getGMapPageTitle());
  
            return parent::_prepareLayout();
        }
    }
    
    public function getAllStores()
    {
       
        $collection = $this->collectionFactory->create()->addFieldToFilter('is_active', 1)
        ->setOrder('creation_time', 'DESC');
        return $collection;
    }
}
