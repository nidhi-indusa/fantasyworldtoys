<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
/**
 * Created by PhpStorm.
 * User: leandro
 * Date: 1/31/17
 * Time: 4:35 PM
 */

namespace CyberSource\ECheck\Gateway\Command;

use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;

abstract class CaptureCaller
{
    /**
     * @var \Magento\Payment\Gateway\Command\CommandPoolInterface
     */
    protected $commandPool;

    /**
     * @var CommandPoolInterface
     */
    public function __construct(CommandPoolInterface $commandPool)
    {
        $this->commandPool = $commandPool;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @api
     */
    public function capture(InfoInterface $payment, $amount)
    {
        /** @var CommandInterface $captureGatewayCommand */
        $captureGatewayCommand = $this->commandPool->get('capture');

        $captureGatewayCommand->execute([
            'payment' => $payment,
            'amount' => $amount
        ]);
    }
}
