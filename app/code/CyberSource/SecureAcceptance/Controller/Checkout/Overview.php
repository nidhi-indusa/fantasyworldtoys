<?php

namespace CyberSource\SecureAcceptance\Controller\Checkout;

use CyberSource\Core\Service\CyberSourceSoapAPI;
use CyberSource\SecureAcceptance\Model\Payment;
use CyberSource\SecureAcceptance\Model\ResourceModel\Token\Collection;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\State;
use Magento\Quote\Model\QuoteRepository;

class Overview extends \Magento\Multishipping\Controller\Checkout\Overview
{
    /**
     * @var Collection
     */
    private $tokenCollection;

    /**
     * @var CyberSourceSoapAPI
     */
    private $cyberSourceSoapApi;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \CyberSource\Core\Model\ResourceModel\Token\Collection $tokenCollection,
        CyberSourceSoapAPI $cyberSourceSoapAPI,
        QuoteRepository $quoteRepository
    ) {
        parent::__construct($context, $customerSession, $customerRepository, $accountManagement);
        $this->tokenCollection = $tokenCollection;
        $this->cyberSourceSoapApi = $cyberSourceSoapAPI;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Multishipping checkout place order page
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->_validateMinimumAmount()) {
            return;
        }

        $this->_getState()->setActiveStep(State::STEP_OVERVIEW);

        try {
            $payment = $this->getRequest()->getPost('payment', []);
            $payment['checks'] = [
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
            ];

            $tokenParts = explode("_", $payment['method']);
            $token = $this->getTokenById($tokenParts[2]);

            $this->_getCheckout()->getQuote()->getPayment()->setAdditionalInformation('is_multishipping_vault', true);
            $this->_getCheckout()->getQuote()->getPayment()->setAdditionalInformation('tokenData', serialize($token));

            if (strstr($payment['method'], Payment::CODE)) {
                $payment['method'] = Payment::CODE;
            }

            $this->_getCheckout()->setPaymentMethod($payment);

            $this->_getState()->setCompleteStep(State::STEP_BILLING);

            $this->_view->loadLayout();
            $this->_view->renderLayout();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('*/*/billing/');
        } catch (\Exception $e) {
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            $this->messageManager->addException($e, __('We cannot open the overview page.'));
            $this->_redirect('*/*/billing/');
        }
    }

    private function getTokenById($id)
    {
        $this->tokenCollection->addFieldToFilter('token_id', $id);
        $this->tokenCollection->load();
        if ($this->tokenCollection->getSize() > 0) {
            $token = $this->tokenCollection->getFirstItem();
            return $token->getData();
        }

        return null;
    }
}
