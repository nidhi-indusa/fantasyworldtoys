<?php
/**
 * @copyright   Copyright (c) http://www.manadev.com
 * @license     http://www.manadev.com/license  Proprietary License
 */

namespace Manadev\Core;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Layout;
use Magento\Framework\App\Request\Http as Request;

class LayoutLogger
{
    /**
     * @var Request
     */
    protected $request;

    public function __construct(RequestInterface $request) {
        $this->request = $request;
    }

    public function log(Layout $layout) {
        $filename = BP . '/var/log/mana/' . $this->getFileName() . '.xml';
        for ($i = 2; ; $i++) {
            if (!file_exists($filename)) {
                break;
            }
            $filename = BP . '/var/log/mana/' . $this->getFileName() . '-' . $i . '.xml';
        }
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0777, true);
        }
        file_put_contents($filename, $layout->getUpdate()->asString());
    }

    protected function getFileName() {
        return sprintf("%s/%s-%s",
            'layout',
            PHP_SAPI !== 'cli' ? $_SERVER['REMOTE_ADDR'] . '-' . $this->getUrl() : 'cli',
            date("Y-m-d-H-i-s")
        );
    }

    protected function getUrl() {
        $result = trim($this->request->getRequestUri(), '/');
        $specialAndReservedCharacters = ";/?:@=&$+!*'(),";

        return str_replace(str_split($specialAndReservedCharacters), '_', $result);
    }
}