<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CyberSource\PayPal\Observer;

use CyberSource\PayPal\Helper\Shortcut\Factory;
use Magento\Framework\Event\ObserverInterface;
use CyberSource\PayPal\Model\Config as PaypalConfig;
use Magento\Framework\Event\Observer as EventObserver;

/**
 * PayPal module observer
 */
class AddPaypalShortcutsObserver implements ObserverInterface
{
    /**
     * @var Factory
     */
    protected $shortcutFactory;

    /**
     * @var PaypalConfig
     */
    protected $paypalConfig;

    /**
     * Constructor
     *
     * @param Factory $shortcutFactory
     * @param PaypalConfig $paypalConfig
     */
    public function __construct(
        Factory $shortcutFactory,
        PaypalConfig $paypalConfig
    ) {
        $this->shortcutFactory = $shortcutFactory;
        $this->paypalConfig = $paypalConfig;
    }

    /**
     * Add PayPal shortcut buttons
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Catalog\Block\ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();
        $blocks = [
            \CyberSource\PayPal\Block\Express\InContext\Minicart\Button::class =>
                PaypalConfig::CODE,
            \CyberSource\PayPal\Block\Express\Shortcut::class => PaypalConfig::CODE
        ];
        foreach ($blocks as $blockInstanceName => $paymentMethodCode) {
            if (!$this->paypalConfig->isActive()) {
                continue;
            }

            $params = [
                'shortcutValidator' => $this->shortcutFactory->create($observer->getEvent()->getCheckoutSession()),
            ];

            // we believe it's \Magento\Framework\View\Element\Template
            $shortcut = $shortcutButtons->getLayout()->createBlock(
                $blockInstanceName,
                '',
                $params
            );
            $shortcut->setIsInCatalogProduct(
                $observer->getEvent()->getIsCatalogProduct()
            )->setShowOrPosition(
                $observer->getEvent()->getOrPosition()
            );
            $shortcutButtons->addShortcut($shortcut);
        }
    }
}
