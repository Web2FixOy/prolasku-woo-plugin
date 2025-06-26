# README #

ProLasku WooCommerce Plugin v.1.1

### What is this repository for? ###

* The plugin will enable synchronisation between WooCommerce and PL CMS for products, categories, taxes, brands, customers and orders
* Version 1.1
* [Learn Markdown](https://bitbucket.org/tutorials/markdowndemo)

### How do I get set up? ###

* IMPORT THE PLUGIN VIA WP Plugin import

### Contribution guidelines ###

* An API key is required for 2 ways communications PL <> WP
* To use the hook:
* POST   /wp-json/easycms/v1/product
* To update: POST   /wp-json/easycms/v1/product/{pid}/update
* To delete: POST   /wp-json/easycms/v1/product/{pid}/delete

### Questions / Support? ###

* suppoort@proinvoicer.com

### Endpoints

#### Products:

CREATE: POST wp-json/easycms/v1/product/

UPDATE: POST wp-json/easycms/v1/product/{id]/update

DELETE: POST wp-json/easycms/v1/product/{id]/delete


#### Categories:
Pass payload as same as API response.
CREATE/UPDATE: POST wp-json/easycms/v1/category/

DELETE: POST wp-json/easycms/v1/category/delete


#### Stock Locations:
Pass payload as same as API response.

CREATE/UPDATE: POST wp-json/easycms/v1/stock-location/

#### Taxes:
Pass payload as same as API response. Due to the structure of WC Taxes, it cannot be edited, you have to delete and re-insert

CREATE:  POST wp-json/easycms/v1/tax/

DELETE:  POST wp-json/easycms/v1/tax/delete

#### Brands:
Pass payload as same as API response. Due to the structure of WC Taxes, it cannot be edited, you have to delete and re-insert

CREATE:  POST wp-json/easycms/v1/brand/

DELETE:  POST wp-json/easycms/v1/brand/delete

### Users:
Pass payload as same as API response.

CREATE/UPDATE: POST wp-json/easycms/v1/user

DELETE:       POST wp-json/easycms/v1/user/delete



#### OTHER(s)
To re-enable the stuck sync button for products go to db > wp_options > easycms_wp_product_sync_status and remove the option

PHP settings 
memory_limit 512M


PLUGINS:
Woocommerce
WooCommerce Multilingual
WPML
WPML MANAGEMENT
WPML STRING TRANSLATIONS



#### Update by
* Hossein Farahkordmahaleh -> h.farah61@gmail.com