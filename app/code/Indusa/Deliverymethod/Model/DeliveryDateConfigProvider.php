<?php
namespace Indusa\Deliverymethod\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\ObjectManagerInterface;
class DeliveryDateConfigProvider implements ConfigProviderInterface
{
    
    const XPATH_FORMAT = 'indusa_deliverydatemethod/general/format';
    const XPATH_DISABLED = 'indusa_deliverydatemethod/general/disabled';
    const XPATH_HOURMIN = 'indusa_deliverydatemethod/general/hourMin';
    const XPATH_HOURMAX = 'indusa_deliverydatemethod/general/hourMax';
    const XPATH_MAXORDERS = 'indusa_deliverydatemethod/general/maxorders';
    const XPATH_CANBEDELIVERED = 'indusa_deliverydatemethod/general/show_hide_canBeDelivered';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
   protected $objectManager;
   
    protected $quoteRepository;
    protected $jsonHelper;
    protected $_request;
    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    
     /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ObjectManagerInterface $objectManager,
        \Magento\Quote\Model\QuoteRepository $quoteRepository, 
        \Magento\Framework\App\RequestInterface $request, 
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->objectManager = $objectManager;
        
         $this->quoteRepository = $quoteRepository;
        $this->_request = $request;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * {@inheritdoc}
     */
    
    
    public function getConfig()
    {
        $store = $this->getStoreId();
        $disabled = $this->scopeConfig->getValue(self::XPATH_DISABLED, ScopeInterface::SCOPE_STORE, $store);
        $hourMin = $this->scopeConfig->getValue(self::XPATH_HOURMIN, ScopeInterface::SCOPE_STORE, $store);
        $hourMax = $this->scopeConfig->getValue(self::XPATH_HOURMAX, ScopeInterface::SCOPE_STORE, $store);
        $format = $this->scopeConfig->getValue(self::XPATH_FORMAT, ScopeInterface::SCOPE_STORE, $store);
        $maxorders = $this->scopeConfig->getValue(self::XPATH_MAXORDERS, ScopeInterface::SCOPE_STORE, $store);
        $show_hide_canBeDelivered = $this->scopeConfig->getValue(self::XPATH_CANBEDELIVERED, ScopeInterface::SCOPE_STORE, $store);
        
        
        $noday = 0;
        if($disabled == -1) {
            $noday = 1;
        }
        
        //$date = '2017-11-06';
       // $show_hide_canBeDelivered = $this->getCheckDeliveryDateStatus($date);
        
        $config = [
            'shipping' => [
                'deliverydatemethod' => [
                    'format' => $format,
                    'disabled' => $disabled,
                    'noday' => $noday,
                    'hourMin' => $hourMin,
                    'hourMax' => $hourMax,
                    'maxOrders' => $maxorders,
                    'show_hide_canBeDelivered' => $show_hide_canBeDelivered
                ]
            ]
        ];
        
        
        return $config;
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getStoreId();
    }
   
}