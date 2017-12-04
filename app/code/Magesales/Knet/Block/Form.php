<?php
namespace Magesales\Knet\Block;

class Form extends \Magento\Payment\Block\Form
{
    protected $_template = 'Magesales_Knet::form.phtml';
	
	protected $_methodCode = 'knet';

	public function getMethodCode()
    {
        return $this->_methodCode;
    }
}
