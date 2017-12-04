<?php
namespace Indusa\Webservices\Block\Adminhtml\Requestqueue\Edit;

/**
 * Admin page left menu
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('requestqueue_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Request queue Information'));
    }
}