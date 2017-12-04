<?php 
namespace Magesales\Knet\Controller;
use Magento\Framework\Controller\ResultFactory;
abstract class Main extends \Magento\Framework\App\Action\Action
{
    protected $_order;
	protected $_customerSession;
    protected $resultPageFactory;
    protected $checkoutSession;
    protected $orderRepository;
	protected $registry;
	protected $_region;
	protected $_country;
	protected $_storeManager;
	protected $response;
	protected $quoteManagement;
	protected $logger;
	protected $scopeConfig;
	protected $order;
	protected $convertorder;
	protected $knetpayment;
	
	/**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
		\Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Directory\Model\RegionFactory $region,
		\Magento\Directory\Model\CountryFactory $country,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Response\Http $response,
		\Psr\Log\LoggerInterface $logger,
		\Magento\Sales\Model\OrderFactory $order,
		\Magento\Sales\Model\Convert\Order $convertorder,
		\Magento\Quote\Model\QuoteManagement $quoteManagement,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magesales\Knet\Model\Payment $knetpayment
	) {
        $this->_customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
		$this->_region = $region;
		$this->_country = $country;
		$this->registry = $registry;
		$this->_storeManager = $storeManager;
		$this->response = $response;
		$this->quoteManagement = $quoteManagement;
		$this->logger = $logger;
		$this->scopeConfig = $scopeConfig;
		$this->order = $order;
		$this->convertorder = $convertorder;
		$this->knetpayment = $knetpayment;
		$this->scopeConfig = $scopeConfig;
		parent::__construct($context);
    }

	public function getOrder()
    {
        if ($this->_order == null)
        {
            $session = $this->checkoutSession;
            $this->_order = $this->order->create();
            $this->_order->loadByIncrementId($session->getLastRealOrderId());
        }
        return $this->_order;
    }
	
	protected function getConfig($path) {
		$path = 'payment/knet/' . $path;
		return $this->scopeConfig->getValue($path,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
}


