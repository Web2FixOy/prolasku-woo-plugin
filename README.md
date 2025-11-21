# README #

ProLasku WooCommerce Plugin v.2.3

### Version History ###

#### Version 2.3 (2025-11-21) ####
- **Performance**: Optimized all database queries for large datasets (10,000+ products)

#### Version 2.2 (2025-10-15) ####
- **Security**: Added Bcrypt password hash functionality with settings interface

#### Version 2.1 (2025-09-28) ####
- **Image Management**: Added abandoned product image detection and deletion with disk space recovery

#### Version 2.0 (2025-09-12) ####
- **Cleanup Tools**: Added orphaned PID cleanup, corrupted data cleanup, and stale PID cleanup

#### Version 1.9 (2025-08-30) ####
- **Inventory Features**: Added draft products count, synced products count, and translation statistics

#### Version 1.8 (2025-08-22) ####
- **Product Management**: Added ultra-fast bulk deletion, category cleanup, and product deletion by category

#### Version 1.7 (2025-08-18) ####
- **AJAX**: Implemented 20+ new AJAX handlers for product management operations

#### Version 1.6 (2025-08-12) ####
- **Admin**: Added inventory and cleanup tabs with configurations subtabs for API settings and password hash

#### Version 1.2 -1.5 (2025-08-08) ####
- **UI**: Added tab state persistence and password hash settings form with AJAX handling

#### Version 1.1 (2025-08-05) ####
- Upgraded to be compatible with WordPress version 6.8.2
- Enhanced JavaScript functionality with 98+ lines of improvements
- Improved AJAX handling with 110+ lines of new code
- Optimized product component with significant refactoring (619 lines restructured)
- Enhanced utility functions and tax component improvements
- Updated admin interface components and templates

#### Version 1.0 (2023-09-21) ####
- Initial plugin release
- Basic synchronization between WooCommerce and PL CMS
- Support for products, categories, taxes, brands, customers, and orders
- Admin interface with settings and logs
- API endpoints for CRUD operations

### What is this repository for? ###
* Wordpress version >= 6.8.2
* The plugin will enable synchronisation between WooCommerce and PL CMS for products, categories, taxes, brands, customers and orders
* Version 2.3
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



### Development Milestones ###

#### Working Version for WordPress 6.8.2 & Above (2025-08-05) ####
- Finalized compatibility with WordPress 6.8.2
- Cleaned up development files and optimized codebase
- Enhanced product component functionality

#### Final Working Version for WordPress 6.8.2 (2025-08-05) ####
- Removed unnecessary code and optimized product component
- Streamlined plugin structure for production deployment

#### Readme Update (2025-08-05) ####
- Updated documentation to reflect current plugin status
- Added .gitignore for better version control management

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



