<?php
	use Magento\Framework\App\Bootstrap;
	include('../app/bootstrap.php');
	$bootstrap = Bootstrap::create(BP, $_SERVER);
	$objectManager = $bootstrap->getObjectManager();
	$storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface'); 
	$baseUrl= $storeManager->getStore()->getBaseUrl();
	// Redirect to error page
	$currentUrl = $baseUrl.$_SERVER['REQUEST_URI'];
	$urlArray = parse_url($currentUrl);
	
	$url = $baseUrl . "knet/payment/cancel?" . (!empty($urlArray['query']) ? $urlArray['query'] : '');
	\Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug(print_r(['Error.php Called' => $url], true));
	header("Location: " . $url);
	exit;
?>