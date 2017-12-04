<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CyberSource\ECheck\Gateway\Config\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\UrlInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'cybersourceecheck';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param Config $config
     * @param Repository $assetRepo
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Config $config,
        Repository $assetRepo,
        RequestInterface $request,
        LoggerInterface $logger,
        UrlInterface $urlBuilder
    ) {
        $this->config = $config;
        $this->assetRepo = $assetRepo;
        $this->logger = $logger;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    public function getECheckImageUrl()
    {
        return $this->getViewFileUrl('CyberSource_ECheck::check_sample.jpg');
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $isECheckActive = $this->config->isActive();

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $isECheckActive,
                    'title' => $this->config->getTitle(),
                    'echeckImage' => $this->getECheckImageUrl()
                ]
            ]
        ];
    }
}
