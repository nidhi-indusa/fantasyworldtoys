<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @package CyberSource\Core\Setup
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    public function install(
        SchemaSetupInterface $installer,
        ModuleContextInterface $context
    ) {
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $installer->getConnection()->dropTable($installer->getTable('cybersource_payment_token'));
            $table = $installer->getTable('cybersource_payment_token');
            if ($installer->getConnection()->isTableExists($table) != true) {
                $table = $installer->getConnection()->newTable($table)
                    ->addColumn(
                        'token_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true
                        ],
                        'Token Id'
                    )
                    ->addColumn(
                        'created_date',
                        \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                        null,
                        [
                            'nullable' => true
                        ],
                        'Created Date'
                    )
                    ->addColumn(
                        'customer_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false,
                            'default' => '0'
                        ],
                        'Customer Id'
                    )
                    ->addColumn(
                        'order_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => false
                        ],
                        'Order Id'
                    )
                    ->addColumn(
                        'quote_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => false,
                        ],
                        'Quote Id'
                    )
                    ->addColumn(
                        'payment_token',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => false,
                        ],
                        'Payment Token'
                    )
                    ->addColumn(
                        'customer_email',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => false
                        ],
                        'Customer Email'
                    )
                    ->addColumn(
                        'store_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false
                        ],
                        'Store Id'
                    )
                    ->addColumn(
                        'card_default',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false,
                            'default' => '0'
                        ],
                        'Cart Default'
                    )
                    ->addColumn(
                        'card_expire',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false,
                        ],
                        'Cart Expire'
                    )
                    ->addColumn(
                        'card_type',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => false,
                        ],
                        'Cart Type'
                    )
                    ->addColumn(
                        'cc_last4',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => false,
                        ],
                        'Cc Last 4'
                    )
                    ->addColumn(
                        'payment_type',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => false,
                        ],
                        'Payment Type'
                    )
                    ->addColumn(
                        'updated_date',
                        \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                        null,
                        [
                            'nullable' => false,
                        ],
                        'Updated Date'
                    )
                    ->addColumn(
                        'card_expiry_date',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => true
                        ],
                        'Card Expiry Date'
                    )
                    ->addColumn(
                        'address_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => true
                        ],
                        'Address ID'
                    )
                    ->addColumn(
                        'reference_number',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => true
                        ],
                        'Reference Number'
                    )
                    ->addColumn(
                        'authorize_only',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => true,
                            'default' => 0
                        ],
                        'Authorize Only'
                    )
                    ->addColumn(
                        'transaction_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => true
                        ],
                        'Transaction ID'
                    )
                    ->addColumn(
                        'cc_number',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => true
                        ],
                        'CC Number'
                    )
                    ->setComment('Token Table')
                    ->setOption('type', 'InnoDB')
                    ->setOption('charset', 'utf8');
                $installer->getConnection()->createTable($table);
            }
            $installer->endSetup();
        }
    }
}
