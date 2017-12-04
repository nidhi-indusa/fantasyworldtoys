# m2-weltpixel-custom-footer

### Installation

Dependencies:
 - m2-weltpixel-backend

With composer:

```sh
$ composer config repositories.welpixel-m2-weltpixel-custom-footer git git@github.com:rusdragos/m2-weltpixel-custom-footer.git
$ composer require weltpixel/m2-weltpixel-custom-footer:dev-master
```

Manually:

Copy the zip into app/code/WeltPixel/CustomFooter directory


#### After installation by either means, enable the extension by running following commands:

```sh
$ php bin/magento module:enable WeltPixel_CustomFooter --clear-static-content
$ php bin/magento setup:upgrade
```
