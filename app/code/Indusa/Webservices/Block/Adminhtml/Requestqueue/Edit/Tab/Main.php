<?php

namespace Indusa\Webservices\Block\Adminhtml\Requestqueue\Edit\Tab;

/**
 * Requestqueue edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Indusa\Webservices\Model\Status
     */
    protected $_status;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Indusa\Webservices\Model\Status $status,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_status = $status;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /* @var $model \Indusa\Webservices\Model\BlogPosts */
        $model = $this->_coreRegistry->registry('requestqueue');

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('page_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Item Information')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

		
        $fieldset->addField(
            'request_id',
            'text',
            [
                'name' => 'request_id',
                'label' => __('Request Id'),
                'title' => __('Request Id'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'request_type',
            'text',
            [
                'name' => 'request_type',
                'label' => __('Request Type'),
                'title' => __('Request Type'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'request_url',
            'text',
            [
                'name' => 'request_url',
                'label' => __('Request Url'),
                'title' => __('Request Url'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'request_xml',
            'text',
            [
                'name' => 'request_xml',
                'label' => __('Request xml'),
                'title' => __('Request xml'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'request_datetime',
            'text',
            [
                'name' => 'request_datetime',
                'label' => __('Request Date'),
                'title' => __('Request Date'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'processed',
            'text',
            [
                'name' => 'processed',
                'label' => __('Processed'),
                'title' => __('Processed'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'processed_at',
            'text',
            [
                'name' => 'processed_datetime',
                'label' => __('Processsed Date'),
                'title' => __('Processsed Date'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'acknowledgment',
            'text',
            [
                'name' => 'acknowledgment',
                'label' => __('Acknowledgment'),
                'title' => __('Acknowledgment'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'ack_datetime',
            'text',
            [
                'name' => 'ack_datetime',
                'label' => __('Ack date'),
                'title' => __('Ack date'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'processed_list',
            'text',
            [
                'name' => 'processed_list',
                'label' => __('Process list'),
                'title' => __('Process list'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'error_list',
            'text',
            [
                'name' => 'error_list',
                'label' => __('Error List'),
                'title' => __('Error List'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
       
					
        $fieldset->addField(
            'created_at',
            'text',
            [
                'name' => 'created_at',
                'label' => __('Created at'),
                'title' => __('Created at'),
				
                'disabled' => $isElementDisabled
            ]
        );
					
        $fieldset->addField(
            'updated_at',
            'text',
            [
                'name' => 'updated_at',
                'label' => __('Updated at'),
                'title' => __('Updated at'),
				
                'disabled' => $isElementDisabled
            ]
        );
					

        if (!$model->getId()) {
            $model->setData('is_active', $isElementDisabled ? '0' : '1');
        }

        $form->setValues($model->getData());
        $this->setForm($form);
		
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Item Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Item Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
    
    public function getTargetOptionArray(){
    	return array(
    				'_self' => "Self",
					'_blank' => "New Page",
    				);
    }
}
