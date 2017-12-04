<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fantasyworld\Fwtheme\Setup;

use Magento\Framework\Setup;

class Installer implements Setup\SampleData\InstallerInterface {

    /**
     * @var \Magento\CmsSampleData\Model\Page
     */
    private $page;

    /**
     * @var \Magento\CmsSampleData\Model\Block
     */
    private $block;

    /**
     * @param \Fantasyworld\Fwtheme\Model\Page $page
     * @param \Fantasyworld\Fwtheme\Model\Block $block
     */
    public function __construct(
    \Fantasyworld\Fwtheme\Model\Page $page, 
            \Fantasyworld\Fwtheme\Model\Block $block
    ) {
        $this->page = $page;
        $this->block = $block;
    }

    /**
     * {@inheritdoc}
     */
    public function install() {

        //$this->page->install(['Fantasyworld_Fwtheme::fixtures/pages/pages.csv']);
        $this->page->install(
                [

                    'Fantasyworld_Fwtheme::DemoPages/pages.csv',
                ]
        );
        $this->block->install(
                [

                    'Fantasyworld_Fwtheme::DemoBlocks/blocks.csv',
                ]
        );
    }

}
