<?php
namespace Magesales\Knet\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Payment\Helper\Data as PaymentHelper;

class KnetConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var PaypalHelper
     */
    protected $paypalHelper;

    /**
     * @var string[]
     */
    /*protected $methodCodes = [
        Config::METHOD_WPP_BML,
        Config::METHOD_WPP_PE_EXPRESS,
        Config::METHOD_WPP_EXPRESS,
        Config::METHOD_WPP_PE_BML
    ];*/

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;
    
    protected $checkoutSession;

    /**
     * @param ConfigFactory $configFactory
     * @param ResolverInterface $localeResolver
     * @param CurrentCustomer $currentCustomer
     * @param PaypalHelper $paypalHelper
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        //ConfigFactory $configFactory,
        ResolverInterface $localeResolver,
        CurrentCustomer $currentCustomer,
        \Magento\Checkout\Model\Session $checkoutSession,
        //PaypalHelper $paypalHelper,
        PaymentHelper $paymentHelper
    ) {
        $this->localeResolver = $localeResolver;
        //$this->config = $configFactory->create();
        $this->currentCustomer = $currentCustomer;
        //$this->paypalHelper = $paypalHelper;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutSession = $checkoutSession;

        //foreach ($this->methodCodes as $code) {
            //$this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
        //}
        $code = 'knet';
        $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $code = 'knet';
        $config = [];
        
        if ($this->methods[$code]->isAvailable($this->checkoutSession->getQuote())) {
            $config = [];
            $config['payment'] = [];
            $config['payment']['knet']['redirectUrl'] = [];
            $config['payment']['knet']['redirectUrl'][$code] = $this->getMethodRedirectUrl($code);
			$config['payment']['knet'][$code]['instructions'] = $this->methods[$code]->getInstructions();
        }
        
        return $config;
    }

    /**
     * Return redirect URL for method
     *
     * @param string $code
     * @return mixed
     */
    protected function getMethodRedirectUrl($code)
    {
        return $this->methods[$code]->getOrderPlaceRedirectUrl();
    }
}
