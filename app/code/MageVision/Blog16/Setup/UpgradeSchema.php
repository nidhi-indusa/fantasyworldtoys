<?php

namespace MageVision\Blog16\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '2.0.2') < 0) {

            $setup->startSetup();

            $quoteAddressTable = 'quote';
            $orderTable = 'sales_order';

            //Quote address table
            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($quoteAddressTable), 'ax_store_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => 11,
                'nullable' => false,
                'default' => 0,
                'comment' => 'AxStore id Custom Test'
                    ]
            );

            //Order address table
            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($orderTable), 'ax_store_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => 11,
                'nullable' => false,
                'default' => 0,
                'comment' => 'AxStore id Custom Test'
                    ]
            );





            $orderTable = 'sales_order_item';
            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($orderTable), 'ax_store_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => 11,
                'nullable' => false,
                'default' => 0,
                'comment' => 'AxStoreId'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.3') < 0) {

            $setup->startSetup();

            $quoteAddressTable = 'quote_item';

            //Quote item table
            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($quoteAddressTable), 'ax_store_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => 11,
                'nullable' => false,
                'default' => 0,
                'comment' => 'AxStore id'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.4') < 0) {

            $setup->startSetup();

            $quoteTable = 'quote';

            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($quoteTable), 'delivery_from', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Deliver from Warehouse/Store',
                ['default' => 'Warehouse'],
                    ]
            );


            $quoteAddressTable = 'quote_item';

            //Quote item table
            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($quoteAddressTable), 'transfer_order_quantity', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'default' => 0.0000,
                'comment' => 'Transfer Order Quantity'
                    ]
            );


            $orderTable = 'sales_order_item';
            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($orderTable), 'transfer_order_quantity', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'default' => 0.0000,
                'comment' => 'Transfer Order Quantity'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.5') < 0) {

            $setup->startSetup();

            $quoteTable = 'quote';
            $orderTable = 'sales_order';

            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($quoteTable), 'delivery_method', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => false,
                  'comment' => 'Deliver method homedelivery/clickandcollect',     
                    ]
            );


            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($orderTable), 'delivery_method', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                 'nullable' => false,
                  'comment' => 'Deliver method homedelivery/clickandcollect',       
                    ]
            );
        }
        
        
         if (version_compare($context->getVersion(), '2.0.6') < 0) {

            $setup->startSetup();

              $setup->startSetup();

            $quoteAddressTable = 'quote';
            $orderTable = 'sales_order';

            //Quote address table
            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($quoteAddressTable), 'location_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => 11,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Location id'
                    ]
            );

            //Order address table
            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($orderTable), 'location_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => 11,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Location id'
                    ]
            );
            
            
           $setup->startSetup();

           $orderTable = 'sales_order';

            $table = $installer->getConnection()
                    ->addColumn(
                    $setup->getTable($orderTable), 'delivery_from', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Deliver from Warehouse/Store',
                ['default' => 'Warehouse'],
                    ]
            );
            
            
        }


        $installer->endSetup();
    }

}
