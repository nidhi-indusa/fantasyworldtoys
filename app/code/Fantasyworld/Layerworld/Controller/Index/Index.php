<?php
 
namespace Fantasyworld\Layerworld\Controller\Index;
 
use Magento\Framework\App\Action\Context;
 
class Index extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
 
    public function __construct(Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }
    
    
    public function getAllManufacturer(){
        $manufacturerOptions = $this->_resultPageFactory->get('manufacturer')->getOptions();       
        $values = array();
        foreach ($manufacturerOptions as $manufacturerOption) { 
           //$manufacturerOption->getValue();  // Value
            $values[] = $manufacturerOption->getLabel();  // Label
        }
        return $values;
    } 
    
    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();
        return $resultPage;
    }
}