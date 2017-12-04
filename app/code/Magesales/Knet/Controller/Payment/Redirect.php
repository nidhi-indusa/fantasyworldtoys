<?php
namespace Magesales\Knet\Controller\Payment;
use Magesales\Knet\Controller\Main;
use Magento\Framework\Controller\ResultFactory;

class Redirect extends Main
{
    public function execute()
    {
		$resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
		$session = $this->checkoutSession;
        $session->setKnetPaymentQuoteId($session->getQuoteId());

        $order = $this->getOrder();

        if (!$order->getId())
        {
            $this->norouteAction();
            return;
        }

        $order->addStatusToHistory($order->getStatus(),__('Customer was redirected to Knet'));
        $order->save();

        $this->getResponse()->setBody($resultPage->getLayout()->createBlock('\Magesales\Knet\Block\Redirect')->setOrder($order)->toHtml());

        $session->unsQuoteId();
	}
}