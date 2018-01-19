<?php
namespace Indusa\Webservices\Block\Adminhtml\Requestqueue;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Indusa\Webservices\Model\requestqueueFactory
     */
    protected $_requestqueueFactory;

    /**
     * @var \Indusa\Webservices\Model\Status
     */
    protected $_status;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Indusa\Webservices\Model\requestqueueFactory $requestqueueFactory
     * @param \Indusa\Webservices\Model\Status $status
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Indusa\Webservices\Model\RequestQueueFactory $RequestqueueFactory,
        \Indusa\Webservices\Model\Status $status,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_requestqueueFactory = $RequestqueueFactory;
        $this->_status = $status;
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('postGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
        $this->setVarNameFilter('post_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_requestqueueFactory->create()->getCollection();
        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );


		
				$this->addColumn(
					'request_id',
					[
						'header' => __('Request Id'),
						'index' => 'request_id',
					]
				);
				
				$this->addColumn(
					'request_type',
					[
						'header' => __('Request Type'),
						'index' => 'request_type',
					]
				);
				
				/*$this->addColumn(
					'request_url',
					[
						'header' => __('Request Url'),
						'index' => 'request_url',
					]
				);*/
				
				/* $this->addColumn(
				'request_xml',
					[
						'header' => __('Request xml'),
						'index' => 'request_xml',
					]
				); */
				
				
				$this->addColumn(
					'processed',
					[
						'header' => __('Processed'),
						'index' => 'processed',
					]
				);
				
				$this->addColumn(
					'processed_datetime',
					[
						'header' => __('Processsed at'),
						'index' => 'processed_at',
					]
				);
				
				$this->addColumn(
					'acknowledgment',
					[
						'header' => __('Acknowledgment'),
						'index' => 'acknowledgment',
					]
				);
				
				$this->addColumn(
					'ack_datetime',
					[
						'header' => __('Ack date'),
						'index' => 'ack_datetime',
					]
				);
				
				$this->addColumn(
					'processed_list',
					[
						'header' => __('Process list'),
						'index' => 'processed_list',
					]
				);
				
				$this->addColumn(
					'error_list',
					[
						'header' => __('Error List'),
						'index' => 'error_list',
					]
				);
				
				
				
				$this->addColumn(
					'creation_time',
					[
						'header' => __('Created at'),
						'index' => 'created_at',
					]
				);
				
				$this->addColumn(
					'update_time',
					[
						'header' => __('Updated at'),
						'index' => 'updated_at',
					]
				);
				


		
        //$this->addColumn(
            //'edit',
            //[
                //'header' => __('Edit'),
                //'type' => 'action',
                //'getter' => 'getId',
                //'actions' => [
                    //[
                        //'caption' => __('Edit'),
                        //'url' => [
                            //'base' => '*/*/edit'
                        //],
                        //'field' => 'id'
                    //]
                //],
                //'filter' => false,
                //'sortable' => false,
                //'index' => 'stores',
                //'header_css_class' => 'col-action',
                //'column_css_class' => 'col-action'
            //]
        //);
		

		
		   $this->addExportType($this->getUrl('webservices/*/exportCsv', ['_current' => true]),__('CSV'));
		   $this->addExportType($this->getUrl('webservices/*/exportExcel', ['_current' => true]),__('Excel XML'));

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

	
    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {

        $this->setMassactionIdField('id');
        //$this->getMassactionBlock()->setTemplate('Indusa_Webservices::requestqueue/grid/massaction_extended.phtml');
        $this->getMassactionBlock()->setFormFieldName('requestqueue');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('webservices/*/massDelete'),
                'confirm' => __('Are you sure?')
            ]
        );

        $statuses = $this->_status->getOptionArray();

        $this->getMassactionBlock()->addItem(
            'status',
            [
                'label' => __('Change status'),
                'url' => $this->getUrl('webservices/*/massStatus', ['_current' => true]),
                'additional' => [
                    'visibility' => [
                        'name' => 'status',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => __('Status'),
                        'values' => $statuses
                    ]
                ]
            ]
        );


        return $this;
    }
		

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('webservices/*/index', ['_current' => true]);
    }

    /**
     * @param \Indusa\Webservices\Model\requestqueue|\Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
		
        return $this->getUrl(
            'webservices/*/edit',
            ['id' => $row->getId()]
        );
		
    }

	

}