<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Block\Adminhtml\Order\View;

use CyberSource\Core\Helper\Data;

/**
 * Payer Authentication block
 * Class Info
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class View extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{

    /** @var Data */
    private $helper;

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        Data $helper,
        array $data
    ) {
        parent::__construct($context, $registry, $adminHelper, $data);

        $this->helper = $helper;
    }

    /**
     * @return array|\string[]
     */
    public function getAdditionalInformation()
    {
        $additionalInformation = $this->getOrder()->getPayment()->getAdditionalInformation();

        $response = [];
        if (!empty($additionalInformation)) {
            $data = $this->helper->getAdditionalData($additionalInformation);
            foreach ($data as $key => $value) {
                $response[$this->buildReadableKey($key)] = $value;
            }
        }

        return $response;
    }

    /**
     * @return array|\string[]
     */
    public function getPayerAuthenticationAdditionalInformation()
    {
        $additionalInformation = $this->getOrder()->getPayment()->getAdditionalInformation();

        $response = [];
        if (!empty($additionalInformation)) {
            $payerAuthData = $this->helper->getPayerAuthenticationData($additionalInformation);

            foreach ($payerAuthData as $key => $value) {
                $response[$this->buildReadableKey($key)] = $value;
            }
        }

        return $response;
    }

    /**
     * Render the value as an array
     *
     * @param mixed $value
     * @param bool $escapeHtml
     * @return array
     */
    public function getValueAsArray($value, $escapeHtml = false)
    {
        if (empty($value)) {
            return [];
        }
        if (!is_array($value)) {
            $value = [$value];
        }
        if ($escapeHtml) {
            foreach ($value as $_key => $_val) {
                $value[$_key] = $this->escapeHtml($_val);
            }
        }
        return $value;
    }

    /**
     *
     * @param string $key
     * @return string
     */
    private function buildAuthReadableKey($key)
    {
        $key = str_replace("payer_authentication_", "", $key);
        $key = str_replace("_", " ", $key);
        return ucwords($key);
    }
    
    /**
     *
     * @param string $key
     * @return string
     */
    private function buildReadableKey($key)
    {
        $key = implode(" ", preg_split('/(?=[A-Z][a-z]+)/', $key));
        $key = str_replace("_", " ", $key);
        return ucwords($key);
    }
}
