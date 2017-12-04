<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Session\SessionManagerInterface;

class SaveAddress implements ObserverInterface
{
    private $customerSession;
    private $addressDataFactory;
    private $addressRepository;
    private $logger;

    /**
     * SaveAddress constructor.
     * @param SessionManagerInterface $customerSession
     * @param AddressInterfaceFactory $addressDataFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        SessionManagerInterface $customerSession,
        AddressInterfaceFactory $addressDataFactory,
        AddressRepositoryInterface $addressRepository,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->addressDataFactory = $addressDataFactory;
        $this->addressRepository = $addressRepository;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $addresses = $observer->getData('addresses');
        $address = $this->addressDataFactory->create();
        $address->setCustomerId($this->customerSession->getId());

        foreach ($addresses as $key => $value) {
            $address->setData($key, $value);
        }

        try {
            $addressId = $this->addressRepository->save($address)->getId();
            $this->customerSession->setData('address_id', $addressId);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
