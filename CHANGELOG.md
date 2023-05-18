Change Log
---------------------
__1.2.2__
-----
* Added deprecation description inside the Readme. The Shopware 5 plugin will no longer receive new updates.

__1.2.1__
-----
* Added Bancontact Bank code to Online banking transaction type
* Updated Genesis PHP library to version 1.21.6

__1.2.0__
-----
* Added support for 3DSv2 params via the emerchantpay Checkout method
* Added support for SCA Exemption settings via the emerchantpay Checkout method
* Added 3DSv2 parameters handling via Web Payment Form
* Added SCA Exemption parameters handling via Web Payment Form
* Updated Genesis PHP library to version 1.21.4
* Removed emerchantpay Direct Payment Method
* Fixed compatibility issue with PHP 8.0 and Shopware 5.7.16

__1.1.2__
-----
* Added Pix Transaction Type via Web Payment Form
* Updated Genesis PHP lib to version 1.21.2
* Tested up to Shopware 5.7.13

__1.1.1__
-----
* Added Interac Bank code to Online banking transaction type
* Tested up to Shopware 5.7.11

__1.1.0__
-----
* Added Checkout Method config option for enabling Web Payment Form Tokenization service**. Please, contact your account manager before enabling tokenization.
* Added plugin logo visible inside the Shopware plugins list page
* Added PHP 8.X support
* Tested up to Shopware 5.7.8

__1.0.4__
-----
* Updated Genesis PHP SDK library to version 1.20.1
* Updated Card.js to the latests
* Added new transaction type Apple Pay via Web Payment Form with support of its methods:
  * Authorize
  * Sale

__1.0.3__
-----
* Updated Genesis PHP SDK library to version 1.20.0
* Updated Card.js to the latests
* Added new transaction type PayPal via Web Payment Form with support of its methods:
  * Authorize
  * Sale
  * Express
* Updated Google Pay transaction type via Web Payment Form with the latest requirements from the payment gateway

__1.0.2__
-----
* Updated Genesis PHP SDK library to version 1.19.2
* Updated Card.js JavaScript plugin used by the Direct Method
* Added support for Google Pay transaction type via Checkout Method (Web Payment Form)

__1.0.1__
-----
* Added new transaction type Post Finance available in the emerchantpay Checkout Method
* Allowed reference action Void for the following transaction types:
  * Sale
  * Sale (3D-Secure)
* Added Plugin data removal upon uninstall
* Added mechanic for execution of the following transaction types:
  * Trustly Sale
  * InstaDebit Payin
  * iDebit Payin
* Fixed Plugin Config Methods Database Schema
* Fixed the transaction ID generation

__1.0.0__
-----
* Added Initial emerchantpay Plugin Structure
* Added Configurations for Direct and Checkout Methods
* Added GenesisPHP SDK library
* Added Genesis SDK Service
* Added Plugin Logging Service
* Added Checkout and Direct Database Tables
* Added custom Front End Error Page Controller
* Added Payment Data Handler
* Added Checkout Payment
* Added Direct Payment
* Added Return URLs Controller and functionality
* Added functionality for storing the payment transactions' database records
* Added Genesis Notification handler
* Added decoration to the Order Details view
* Added Reference transaction functionality
