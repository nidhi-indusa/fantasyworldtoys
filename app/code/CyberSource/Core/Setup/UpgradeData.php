<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class UpgradeData
 * @package CyberSource\Core\Setup
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(
        ModuleDataSetupInterface $installer,
        ModuleContextInterface $context
    ) {

        $installer->startSetup();
        
        if (version_compare($context->getVersion(), '1.0.10', '<')) {
            $table = $installer->getTable('sales_order_status');
            if ($installer->getConnection()->isTableExists($table) == true) {
                $installer->getConnection()->insert(
                    $installer->getTable('sales_order_status'),
                    [
                        'status' => 'dm_refund_review',
                        'label' => 'DM Refund Review'
                    ]
                );
                $installer->getConnection()->insert(
                    $installer->getTable('sales_order_status_state'),
                    [
                        'status' => 'dm_refund_review',
                        'state' => 'dm_refund_review',
                        'is_default' => 0,
                        'visible_on_front' => 0,
                    ]
                );
            }
            $installer->endSetup();
        }

        if (version_compare($context->getVersion(), '1.0.11', '<')) {
            $table = $installer->getTable('email_template');
            if ($installer->getConnection()->isTableExists($table) == true) {
                $email_content = '{{template config_path="design/email/header_template"}}
                    <table>
                        <tr class="email-intro">
                            <td>
                                <p class="greeting">{{trans "%name," name=$order.getCustomerName()}}</p>
                                <p>
                                    {{trans
                                        "Your order #%increment_id has been cancelled by our fraud detection system.
                                        <strong>%order_status</strong>."
                                        increment_id=$order.increment_id
                                        order_status=$order.getStatusLabel() |raw}}
                                </p>

                                <p>
                                    {{trans "We apologize for any inconvenience and urge you to contact us by email: 
                                    <a href=\"mailto:%store_email\">%store_email</a>" store_email=$store_email |raw}}
                                    {{depend store_phone}}
                                    {{trans "or call us at 
                                    <a href=\"tel:%store_phone">%store_phone</a>\" store_phone=$store_phone |raw}}
                                    {{/depend}} if you believe this was cancelled in error.
                                    {{depend store_hours}}
                                    {{trans "Our hours are
                                    <span class=\"no-link\">%store_hours</span>." store_hours=$store_hours |raw}}
                                    {{/depend}}
                                </p>
                            </td>
                        </tr>
                        <tr class="email-information">
                            <td>
                                {{depend comment}}
                                <table class="message-info">
                                    <tr>
                                        <td>
                                            {{var comment|escape|nl2br}}
                                        </td>
                                    </tr>
                                </table>
                                {{/depend}}
                            </td>
                        </tr>
                    </table>
                    {{template config_path="design/email/footer_template"}}';

                $subject = '{{trans "your %store_name order has been cancelled" store_name=$store.getFrontendName()}}';

                $installer->getConnection()->insert(
                    $installer->getTable('email_template'),
                    [
                        'template_code' => 'DM Fail Transaction',
                        'template_text' => $email_content,
                        'template_styles' => '',
                        'template_type' => 2,
                        'template_subject' => $subject,
                        'template_sender_name' => '',
                        'template_sender_email' => '',
                    ]
                );
            }
            $installer->endSetup();
        }

        $installer->endSetup();
    }
}
