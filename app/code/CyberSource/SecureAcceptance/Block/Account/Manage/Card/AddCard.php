<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Block\Account\Manage\Card;

use CyberSource\SecureAcceptance\Model\Token;

/**
 * Addcard
 */
class AddCard extends \Magento\Customer\Block\Address\Edit
{
    /**
     * @var string
     */
    public $tokenId;

    /**
     * @var Token
     */
    public $token;

    /**
     * @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder
     */
    public $helper;

    /**
     * @var \Magento\Customer\Model\Address
     */
    public $customerAddress;

    /**
     * Addcard constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param Token $tokenModel
     * @param \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $helper
     * @param \Magento\Customer\Model\Address $customerAddress
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \CyberSource\SecureAcceptance\Model\Token $tokenModel,
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $helper,
        \Magento\Customer\Model\Address $customerAddress,
        array $data
    ) {
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $customerSession,
            $addressRepository,
            $addressDataFactory,
            $currentCustomer,
            $dataObjectHelper,
            $data
        );

        $this->token = $tokenModel;
        $this->helper = $helper;
        $this->customerAddress = $customerAddress;

        if ($this->_request->getParam('id')) {
            $this->tokenId = $this->_request->getParam('id');
            $this->token = $tokenModel->load($this->tokenId);
        }
    }

    /**
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('cybersource/manage/createcard');
    }

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->_urlBuilder->getUrl('cybersource/manage/createcard');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        $title = __('Create New Card');
        if ($this->tokenId) {
            $title = __('Update Card Information');
        }
        return $title;
    }

    /**
     * @return bool
     */
    public function getUseIframe()
    {
        return $this->_scopeConfig->getValue(
            'payment/chcybersource/use_iframe',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     *
     * @return bool
     */
    public function isSilent()
    {
        return ($this->_scopeConfig->getValue('payment/chcybersource/secureacceptance_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) != 'web');
    }
    
    
    public function getCcTypes()
    {
        return $this->_scopeConfig->getValue('payment/chcybersource/cctypes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * @param string $addressId
     */
    public function setAddressData($addressId)
    {
        $this->customerAddress->load($addressId);
        $address = $this->getAddress();
        $address->setData('company', $this->customerAddress->getCompany());
        $address->setData('telephone', $this->customerAddress->getTelephone());
        $address->setData('city', $this->customerAddress->getCity());
        $address->setData('country_id', $this->customerAddress->getCountryId());
        $address->setData('postcode', $this->customerAddress->getPostcode());
        $this->setCybesourceRegionId($this->customerAddress->getRegionId());
        
        $street = $this->customerAddress->getStreet();
        if (count($street) < 2) {
            $street[1] = '';
        }
        $this->setStreet($street);
    }
}
