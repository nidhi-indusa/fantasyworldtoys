<?php
namespace Dckap\Newlink\Block;
class Header extends \Magento\Framework\View\Element\Html\Link
{
protected $_template = 'Dckap_Newlink::link.phtml';
public function getHref()
{
return__( 'testuser');
}
public function getLabel()
{
return __('Test Link');
}
}
?>