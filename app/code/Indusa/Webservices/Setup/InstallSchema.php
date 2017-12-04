<?php

namespace Indusa\Webservices\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
		
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.0') < 0){

        $table = $installer->getConnection()
            ->newTable($installer->getTable('request_queue'))
            ->addColumn(
                'id',
                Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn('request_id', Table::TYPE_TEXT, 20, ['nullable' => false],'Unique Request ID recieved from AX')
            ->addColumn('request_type', Table::TYPE_TEXT, 200, ['nullable' => false],'manageProducts/inventoryUpdates/pricingUpdates/orderStatusUpdates/relatedProducts')
            ->addColumn('request_xml', Table::TYPE_TEXT, '1000M', ['nullable' => false], 'Request XML recieved from AX')
            ->addColumn('request_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'Date Time when the request was receieved from AX')    
			->addColumn('processed', Table::TYPE_BOOLEAN, NULL, ['default' => 0], 'Processed Flag')
            ->addColumn('processed_at', Table::TYPE_DATETIME, null, ['nullable' => false], 'Date Time when the request data parsed')        
            ->addColumn('acknowledgment', Table::TYPE_BOOLEAN, NULL, ['default' => 0], 'Acknowledgment Flag ')
            ->addColumn('ack_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'Acknowledgment Sent DateTime')     
            ->addColumn('processed_list', Table::TYPE_TEXT, 4000, [], 'Successful Processed List')
            ->addColumn('error_list', Table::TYPE_TEXT, 4000, [], 'Error List')
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['nullable' => false], 'Request Created Date & time')
            ->addColumn('updated_at', Table::TYPE_DATETIME, null, ['nullable' => false], 'Request Updated Time')
            ->setComment('Integration Request Data from AX');

        $installer->getConnection()->createTable($table);
        
        $table = $installer->getConnection()->newTable($installer->getTable('catalog_product_store_inventory'));
		$table->addColumn('id', Table::TYPE_INTEGER,null,['identity' => true, 'nullable' => false,'primary' => true],'ID');
                $table->addColumn('ax_store_id', Table::TYPE_INTEGER,null,['nullable' => true, 'default' => null],'ax store id');
                $table->addColumn('product_sku', Table::TYPE_TEXT,null,['nullable' => true, 'default' => null],'product sku');
                $table->addColumn('quantity', Table::TYPE_INTEGER,null,['nullable' => true, 'default' => null],'store quantity');
                $table->setComment('store quantity');
        
        $installer->getConnection()->createTable($table);
		
		}
		

        $installer->endSetup();

    }
}