<?php
namespace Fantasyworld\Layerworld\Block;
 
class Layerworld extends \Magento\Framework\View\Element\Template
{
    protected $eavAttributeRepository;
     /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $eavAttributeRepository
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,       
        \Magento\Eav\Api\AttributeRepositoryInterface $eavAttributeRepository
    ) {
        parent::__construct($context);
        $this->eavAttributeRepository = $eavAttributeRepository;
    }

    public function getOptionlist($attributeCode){
        $attributes = $this->eavAttributeRepository->get(\Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE,$attributeCode);
        $options = $attributes->getSource()->getAllOptions(false);
        return $options;                
    }

    
    
    public function getLayerWorldTxt()
    {
        return 'Welcome to Teaem Express world!';
    }
}