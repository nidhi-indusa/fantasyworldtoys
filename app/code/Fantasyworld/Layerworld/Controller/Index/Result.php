<?php

namespace Fantasyworld\Layerworld\Controller\Index;


use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Element\Messages;
use Magento\Framework\View\Result\PageFactory;

class Result extends Action
{
    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory;
    protected $_storeManager;
        protected $_urlInterface;
    /**
     * Result constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Context $context,PageFactory $pageFactory,\Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface)
    {
        $this->resultPageFactory = $pageFactory;
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        //$this->_coreRegistry = $coreRegistry;
       // parent::__construct($context, $coreRegistry);
        parent::__construct($context);
        
    }

    /**
     * The controller action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
          $url = $this->_storeManager->getStore()->getBaseUrl();
          $data['age_group'] = $this->getRequest()->getParam('age_group');
         $data['gender'] = $this->getRequest()->getParam('gender');
         $data['price'] = $this->getRequest()->getParam('price');
        return $this->_redirect($url."giftfinder/?age_group=".$this->getRequest()->getParam('age_group')."&gender=".$this->getRequest()->getParam('gender')."&price=".$this->getRequest()->getParam('price'));
      
    }
}