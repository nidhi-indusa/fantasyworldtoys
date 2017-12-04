<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class PartialPaymentProcessor
 * @package CyberSource\Core\Model\Source
 * @codeCoverageIgnore
 */
class PartialPaymentProcessor implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'aibms',
                'label' => __('AIBMS'),
            ],
            [
                'value' => 'ae_direct',
                'label' => __('American Express Direct')
            ],
            [
                'value' => 'asia_gateway',
                'label' => __('Asia, Middle East and Africa Gateway')
            ],
            [
                'value' => 'atos',
                'label' => __('Atos')
            ],
            [
                'value' => 'barclays',
                'label' => __('Barclays')
            ],
            [
                'value' => 'ccs',
                'label' => __('CCS (CAFIS)')
            ],
            [
                'value' => 'chase',
                'label' => __('Chase Paymentech Solutions')
            ],
            [
                'value' => 'cielo',
                'label' => __('Cielo')
            ],
            [
                'value' => 'comercio',
                'label' => __('Comercio Latino')
            ],
            [
                'value' => 'cybersource_latin',
                'label' => __('CyberSource Latin American Processing')
            ],
            [
                'value' => 'cybersource_visanet',
                'label' => __('CyberSource through VisaNet')
            ],
            [
                'value' => 'fdc_compass',
                'label' => __('FDC Compass')
            ],
            [
                'value' => 'fdc_germany',
                'label' => __('FDC Germany')
            ],
            [
                'value' => 'fdc_nashville_global',
                'label' => __('FDC Nashville Global')
            ],
            [
                'value' => 'fdms_nashville',
                'label' => __('FDMS Nashville')
            ],
            [
                'value' => 'fdms_south',
                'label' => __('FDMS South')
            ],
            [
                'value' => 'gpn',
                'label' => __('GPN')
            ],
            [
                'value' => 'hbos',
                'label' => __('HBoS')
            ],
            [
                'value' => 'hsbc',
                'label' => __('HSBC is the CyberSource name for HSBC U.K')
            ],
            [
                'value' => 'ingenico',
                'label' => __('Ingenico ePayments was previously called Global Collect')
            ],
            [
                'value' => 'jcn',
                'label' => __('JCN Gateway')
            ],
            [
                'value' => 'litle',
                'label' => __('Litle')
            ],
            [
                'value' => 'lloyds_omnipay',
                'label' => __('Lloyds-OmniPay')
            ],
            [
                'value' => 'lloydstsb',
                'label' => __('LloydsTSB Cardnet')
            ],
            [
                'value' => 'moneris',
                'label' => __('Moneris')
            ],
            [
                'value' => 'omnipay_direct',
                'label' => __('OmniPay Direct')
            ],
            [
                'value' => 'omnipay_ireland',
                'label' => __('OmniPay-Ireland')
            ],
            [
                'value' => 'streamline',
                'label' => __('Streamline')
            ],
            [
                'value' => 'tsys',
                'label' => __('TSYS Acquiring Solutions')
            ]
        ];
    }
}
