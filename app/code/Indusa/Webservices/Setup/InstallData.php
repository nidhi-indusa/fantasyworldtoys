<?php

namespace Indusa\Webservices\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface {

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        if (version_compare($context->getVersion(), '1.0.0') < 0) {

            $eavSetup->removeAttribute(\Magento\Catalog\Model\Category::ENTITY, 'ax_category_code');


            $eavSetup->addAttribute(\Magento\Catalog\Model\Category :: ENTITY, 'ax_category_code', [
                'type' => 'varchar',
                'label' => 'Ax Category code',
                'input' => 'text',
                'required' => true,
                'sort_order' => 110,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'General Information',
                "default" => "",
                "class" => "",
                "note" => ""
                    ]
            );            
            
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY,'ax_product_id');
	  
            $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'ax_product_id',
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Ax Product id',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => 0,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );
            
            
            
        }
    }

}
