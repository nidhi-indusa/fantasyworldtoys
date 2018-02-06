<?php
namespace Mageside\SaleCategory\Plugin;
class Layer
{
  public function aroundGetProductCollection(
    \Magento\Catalog\Model\Layer $subject,
    \Closure $proceed
  ) {

    $result = $proceed();
    $result->addAttributeToFilter('price', array('lt' => 100));
    return $result;
  }
}