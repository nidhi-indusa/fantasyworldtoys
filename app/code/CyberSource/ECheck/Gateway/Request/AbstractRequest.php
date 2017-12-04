<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Request;

use CyberSource\Core\Model\ConfigProvider;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use CyberSource\ECheck\Gateway\Config\Config;

abstract class AbstractRequest
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @param Config $config
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     */
    public function __construct(
        Config $config,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
    ) {
        $this->config = $config;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     *  Account type. Possible values:
        C : Checking
        S : Savings (U.S. dollars only)
        X : Corporate checking (U.S. dollars only)
     */
    const ACCOUNT_TYPE = "C";

    /**
     *  TeleCheck
        Accepts only the following values:
        - PPD
        - TEL
        - WEB
     */
    const SEC_CODE = "WEB";

    /**
     * @param $merchantID
     * @param $merchantReferenceCode
     * @return \stdClass
     */
    protected function buildAuthNodeRequest($merchantID, $merchantReferenceCode)
    {
        $request = new \stdClass();
        $request->merchantID = $merchantID;
        $request->partnerSolutionID = 'T54H9OLO';
        $this->config->setMethodCode(ConfigProvider::CODE);
        $developerId = $this->config->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $this->config->setMethodCode(\CyberSource\ECheck\Model\Ui\ConfigProvider::CODE);
        $request->merchantReferenceCode = $merchantReferenceCode;

        return $request;
    }

    /**
     * @param AddressAdapterInterface $address
     * @param string $customerIP
     * @return \stdClass
     */
    protected function buildBillingAddress(AddressAdapterInterface $address, $customerIP)
    {
        $billTo = new \stdClass();
        $billTo->firstName = $address->getFirstname();
        $billTo->lastName = $address->getLastname();
        $billTo->street1 = $address->getStreetLine1();
        $billTo->city = $address->getCity();
        $billTo->state = $address->getRegionCode();
        $billTo->postalCode = $address->getPostcode();
        $billTo->country = $address->getCountryId();
        $billTo->phoneNumber = $address->getTelephone();
        $billTo->email = $address->getEmail();
        $billTo->ipAddress = $customerIP;

        return $billTo;
    }

    /**
     * @param $bankTransitNumber
     * @param $accountNumber
     * @return \stdClass
     */
    protected function buildAccountNode($bankTransitNumber, $accountNumber)
    {
        $check = new \stdClass();
        $check->accountNumber = (string) $accountNumber;
        $check->accountType = self::ACCOUNT_TYPE;
        $check->bankTransitNumber = (string) $bankTransitNumber;
        $check->secCode = self::SEC_CODE;

        return $check;
    }

    /**
     * @param $request
     * @param $items
     * @return mixed
     */
    protected function buildItemsNode($request, $items)
    {
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($items as $i => $item) {
            $request->{'item_'.$i. '_unitPrice'} = $item->getPrice();
        }

        return $request;
    }

    /**
     * @param float $amount
     * @return string
     */
    protected function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }
}
