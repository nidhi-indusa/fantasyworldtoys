<?php
namespace WeltPixel\CustomFooter\Helper;

/**
 * Class Data
 * @package WeltPixel\CustomFooter\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const FOOTER_VERSION_PATH = 'weltpixel_custom_footer/footer/version';
    const FOOTER_PREFIX = 'weltpixel_footer_';

    /**
     * @return string
     */
    public function getFooterVersion()
    {
        $footerVersion = $this->scopeConfig->getValue(self::FOOTER_VERSION_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	    return self::FOOTER_PREFIX . $footerVersion;
    }
}
