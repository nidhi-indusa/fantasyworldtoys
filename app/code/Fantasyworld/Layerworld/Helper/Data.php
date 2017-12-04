<?php
namespace Fantasyworld\Layerworld\Helper;
 
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $productAttributeRepository;
 
    /**
     * @param \Magento\Framework\App\Helper\Context        $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository
    ) {
        parent::__construct($context);
        $this->productAttributeRepository = $productAttributeRepository;
    }
 
    public function getCatalogResourceEavAttribute($attrCode)
    {
        // $attrCode will be attribute code, i.e. 'manufacturer'
        try{
            return $this->productAttributeRepository->get($attrCode)->getOptions();
        }catch(\Exception $e){
            return false;
        }
    }
}