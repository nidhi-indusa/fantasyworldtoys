<?php
	
	namespace Indusa\Webservices\Setup;
	
	use Magento\Framework\Setup\UpgradeSchemaInterface;
	use Magento\Framework\Setup\ModuleContextInterface;
	use Magento\Framework\Setup\SchemaSetupInterface;
	use Magento\Framework\DB\Ddl\Table;
	use Magento\Framework\DB\Adapter\AdapterInterface;
	
	class UpgradeSchema implements UpgradeSchemaInterface{
		
		public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context){
			$installer = $setup;
			$installer->startSetup();
			$orderTable = 'sales_order';
			
			if (version_compare($context->getVersion(), '1.0.1') < 0){
				
				$table = $installer->getConnection()
				->addColumn(
				$setup->getTable($orderTable),
				'synched',
				[
				'type'=> \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
				'comment' => 'Synched Flag',
				'default' => 0,
				'nullable' => true
				]   
				);
				
				$table = $installer->getConnection()
				->addColumn(
				$setup->getTable($orderTable),
				'synched_at',
				[
				'type'=> \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
				'comment' => 'Synched Data Time',
				'nullable' => true
				]   
				);
				
				$table = $installer->getConnection()
				->addColumn(
				$setup->getTable($orderTable),
				'acknowlegment',
				[
				'type'=> \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
				'comment' => 'Acknowledgment Flag',
				'default' => 0,
				'nullable' => true
				]   
				);
				
				$table = $installer->getConnection()
				->addColumn(
				$setup->getTable($orderTable),
				'acknowleged_at',
				[
				'type'=> \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
				'comment' => 'Acknowledgment Time',
				'nullable' => true
				]   
				);
				$table = $installer->getConnection()
				->addColumn(
				$setup->getTable($orderTable),
				'delivery_from',
				[
				'type'=> \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				'length' => 255,
				'comment' => 'Deliver from Warehouse/Store',
				'nullable' => false
				]   
				);
				$table = $installer->getConnection()
				->addColumn(
				$setup->getTable($orderTable),
				'location_id',
				[
				'type'=> \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				'length' => 20,
				'comment' => 'Location of Warehouse/Store',
				'nullable' => false
				]   
				);				
			}
			else if (version_compare($context->getVersion(), '1.0.2') < 0){
			
				//Deleting acknowlegment,acknowleged_at,synched and synched_at column from order table
				$installer->getConnection()->dropColumn($setup->getTable($orderTable), 'acknowlegment', $schemaName = null);
				$installer->getConnection()->dropColumn($setup->getTable($orderTable), 'acknowleged_at', $schemaName = null);
				$installer->getConnection()->dropColumn($setup->getTable($orderTable), 'synched', $schemaName = null);
				$installer->getConnection()->dropColumn($setup->getTable($orderTable), 'synched_at', $schemaName = null);
				
				//Creating sync and sync_at column in order table
				$table = $installer->getConnection()
				->addColumn(
				$setup->getTable($orderTable),
				'sync',
				[
				'type'=> \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
				'comment' => 'Sync Flag for Order sync at AX or not',
				'default' => 0,
				'nullable' => true
				]   
				);
				
				$table = $installer->getConnection()
				->addColumn(
				$setup->getTable($orderTable),
				'sync_at',
				[
				'type'=> \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
				'comment' => 'Sync Data Time',
				'nullable' => true
				]   
				);
				
				//Creating Order_Request_Queue table to sync order at AX end
				$table = $installer->getConnection()
				->newTable($installer->getTable('order_request_queue'))
				->addColumn(
					'id',
					Table::TYPE_SMALLINT,
					null,
					['identity' => true, 'nullable' => false, 'primary' => true],
					'ID'
				)
				->addColumn('request_id', Table::TYPE_TEXT, 20, ['nullable' => false],'Unique Request ID')
				->addColumn('request_type', Table::TYPE_TEXT, 200, ['nullable' => false],'createOrderAndCustomer')
				->addColumn('request_xml', Table::TYPE_TEXT, '1000M', ['nullable' => false], 'Order Request XML')
				->addColumn('request_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'Date and Time when the request sent to AX')    
				->addColumn('response', Table::TYPE_BOOLEAN, NULL, ['default' => 0], 'Response Flag whether the request data received at AX end. Value can be 0 or 1')
				->addColumn('response_at', Table::TYPE_DATETIME, null, ['nullable' => false], 'Date Time when the request data received at AX end')        
				->addColumn('acknowledgment', Table::TYPE_BOOLEAN, NULL, ['default' => 0], 'Acknowledgment Flag ')
				->addColumn('ack_datetime', Table::TYPE_DATETIME, null, ['nullable' => false], 'Acknowledgment Received DateTime')     
				->addColumn('processed_list', Table::TYPE_TEXT, 4000, [], 'Successful Order Processed List')
				->addColumn('error_list', Table::TYPE_TEXT, 4000, [], 'Order Error List')
				->addColumn('created_at', Table::TYPE_DATETIME, null, ['nullable' => false], 'Request Created Date & time')
				->addColumn('updated_at', Table::TYPE_DATETIME, null, ['nullable' => false], 'Request Updated Time')
				->setComment('Order Request Data from AX');

				$installer->getConnection()->createTable($table);
			}
			
			else if (version_compare($context->getVersion(), '1.0.3') < 0){
			$table = $installer->getConnection()
				->addColumn(
				$setup->getTable($orderTable),
				'sent_to_ax',
				[
				'type'=> \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
				'comment' => 'Sent to AX Flag',
				'default' => 0,
				'nullable' => true
				]   
				);
			}
			$installer->endSetup();			
		}
		
	}	