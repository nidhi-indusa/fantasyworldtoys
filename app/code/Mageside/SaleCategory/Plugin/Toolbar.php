<?php
namespace Mageside\SaleCategory\Plugin;
class Toolbar
{

  protected $_objectManager;
  protected $request;

  public function __construct(
    \Magento\Framework\ObjectManagerInterface $objectmanager,
    \Magento\Framework\App\Request\Http $request
  ) {
    $this->_objectManager = $objectmanager;
    $this->request = $request;
  }

  public function aroundSetCollection(
    \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
    \Closure $proceed,
    $request
  ) {
    $result = $proceed($request);

    $this->_collection = $request;
    $category = $this->_objectManager->get('Magento\Framework\Registry')->registry('current_category');
    if($category)
    {
      $page = $this->request->getParam('p');
      if($page == '')
      {
        $page = 1;
      }
      $this->_collection->getCurPage();
      $this->_collection->setCurPage($page);  
    }
    //else
    //{
    //  $this->_collection->setCurPage($this->getCurrentPage());
    //}

    return $result;
  }

}