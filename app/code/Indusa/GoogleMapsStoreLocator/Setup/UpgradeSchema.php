<?php
	
namespace Indusa\GoogleMapsStoreLocator\Setup;

        use Magento\Framework\Setup\UpgradeSchemaInterface;
	use Magento\Framework\Setup\ModuleContextInterface;
	use Magento\Framework\Setup\SchemaSetupInterface;
	use Magento\Framework\DB\Ddl\Table;
	use Magento\Framework\DB\Adapter\AdapterInterface;
	
	class UpgradeSchema implements UpgradeSchemaInterface{
		
		public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context){
			$installer = $setup;
			$installer->startSetup();
			if (version_compare($context->getVersion(), '2.0.0.1') < 0){
                               
                                $setup->startSetup();

                                $Table = 'indusa_googlemapsstorelocator';
                             

                                //indusa_googlemapsstorelocator table
                                $table = $installer->getConnection()
                                    ->addColumn(
                                        $setup->getTable($Table),
                                        'productcustom_message',
                                        [
                                            'type' =>  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                                            'length' => 1500,
                                            'nullable' => false,
                                            'comment' =>'Product Custom Message'
                                        ]
                                    );
                                
                               /* 
                                $table = $installer->getConnection()
                                    ->addColumn(
                                        $setup->getTable($Table),
                                        'ax_storeid',
                                        [
                                            'type' =>  \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                                            'length'   => 5,
                                            'nullable' => false,
                                            'default' => [],
                                            'comment' =>'AX Store ID'
                                        ]
                                    );
                                
                                
                                $table = $installer->getConnection()
                                    ->addColumn(
                                        $setup->getTable($Table),
                                        'google_url',
                                        [
                                            'type' =>  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                                            'length'   => '255',
                                            'nullable' => false,
                                            'default' => [],
                                            'comment' =>'Google Url'
                                        ]
                                    );
                               */
                                
                                
                        	
			}	
			$installer->endSetup();			
		}
		
	}	