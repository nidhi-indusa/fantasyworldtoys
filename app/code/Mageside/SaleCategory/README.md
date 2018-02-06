Magento 2 Sale Category by Mageside
===================================

####Support
    v1.0.4 - Magento 2.1.*-2.2.*
    v1.0.* - Magento 2.1.*

####Change list
    v1.0.3 - Magento 2.2 support checking (updated composer.json)
    v1.0.2 - Updated composer.json
    v1.0.1 - Added dropdown menu for select sale category
    v1.0.0 - Start project

####Installation
    1. Download the archive.
    2. Make sure to create the directory structure in your Magento - 'Magento_Root/app/code/Mageside/SaleCategory'.
    3. Unzip the content of archive to directory 'Magento_Root/app/code/Mageside/SaleCategory'
       (use command 'unzip ArchiveName.zip -d path_to/app/code/Mageside/SaleCategory').
    4. Run the command 'php bin/magento module:enable Mageside_SaleCategory' in Magento root.
       If you need to clear static content use 'php bin/magento module:enable --clear-static-content Mageside_SaleCategory'.
    5. Run the command 'php bin/magento setup:upgrade' in Magento root.
    6. Run the command 'php bin/magento setup:di:compile' if you have a single website and store, 
       or 'php bin/magento setup:di:compile-multi-tenant' if you have multiple ones.
    7. Clear cache: 'php bin/magento cache:clean', 'php bin/magento cache:flush'
    8. Deploy static content: 'php bin/magento setup:static-content:deploy'