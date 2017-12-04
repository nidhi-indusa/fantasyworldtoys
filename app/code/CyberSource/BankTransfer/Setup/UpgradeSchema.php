<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @package CyberSource\Core\Setup
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(
        SchemaSetupInterface $installer,
        ModuleContextInterface $context
    ) {
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('cybersource_ideal_option'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Primary Key'
                )
                ->addColumn(
                    'option_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    100,
                    ['nullable' => false, 'default' => ''],
                    'Option Id'
                )
                ->addColumn(
                    'option_name',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    100,
                    ['nullable' => false, 'default' => ''],
                    'Option Name'
                )
                ->addColumn(
                    'created_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    null,
                    [
                        'nullable' => true
                    ],
                    'Created Date'
                )->setComment("IDEAL option table");
            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}
