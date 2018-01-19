<?php
 
namespace Indusa\AddressValidator\Setup;
 
/**
* Copyright © 2016 Magento. All rights reserved.
* See COPYING.txt for license details.
*/

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
    * {@inheritdoc}
    * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
    */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
          /**
          * Create table 'greeting_message'
          */
          $table = $setup->getConnection()
              ->newTable($setup->getTable('city'))
              ->addColumn(
                  'id',
                  \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                  null,
                  ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                  'ID'
              )
              ->addColumn(
                  'state_id',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  255,
                  ['nullable' => false, 'default' => ''],
                    'State ID'
              )
			   ->addColumn(
                  'city_name',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  255,
                  ['nullable' => false, 'default' => ''],
                    'City Name'
              )
			  ->setComment("City Tbale");
          $setup->getConnection()->createTable($table);
      }
}