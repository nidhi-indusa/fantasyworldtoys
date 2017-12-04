<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fantasyworld\Fwtheme\Block\Adminhtml\System\Config\Form;

use Magento\Store\Model\ScopeInterface;

class Info extends \Magento\Config\Block\System\Config\Form\Field {

    protected $moduleList;

    public function __construct(
    \Magento\Framework\Module\ModuleListInterface $moduleList, \Magento\Backend\Block\Template\Context $context, array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element) {
        $m = $this->moduleList->getOne($this->getModuleName());
        $html = '<div style="padding:12px;background-color:#F1F1F1;border:1px solid #d1d1d1;margin-bottom:5px;font-weight: 600;">
            FwTheme: Free Responsive Theme <span style="color: #F66D1F;">v' . $m['setup_version'] . '</span> was developed by <a href="http://www.fantasyworldtoys.net/" target="_blank">Fantasyworld</a>.
        </div>';

        return $html;
    }

}