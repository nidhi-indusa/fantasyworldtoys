<?php
namespace Fantasyworld\Customcategory\Block\Product\View;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;

class Categories extends AbstractProduct
{

    private $logger;

    public function __construct(Context $context,
                                \Psr\Log\LoggerInterface $logger,
                                array $data = [])
    {
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    public function getCategories()
    {
       
        $product = $this->getProduct();
        $cats = $product->getCategoryCollection()
            ->addAttributeToSelect("name")
            ->addIsActiveFilter();
        return $cats;
    }
}
?>