Change Log
---------------------
__1.0.1__
-----
* Added new transaction type Post Finance available in the emerchantpay Checkout Method (#520)
* Allowed reference action Void for the following transaction types (#520):
  * Sale
  * Sale (3D-Secure)
* Added Plugin data removal upon uninstall (#510)
* Added mechanic for execution of the following transaction types (#513):
  * Trustly Sale
  * InstaDebit Payin
  * iDebit Payin
* Fixed Plugin Config Methods Database Schema (#519)
* Fixed the transaction ID generation (#515)

__1.0.0__
-----
* Added Initial emerchantpay Plugin Structure (#302)
* Added Configurations for Direct and Checkout Methods (#389, #504)
* Added GenesisPHP SDK library (#391)
* Added Genesis SDK Service (#393)
* Added Plugin Logging Service (#394)
* Added Checkout and Direct Database Tables (#396)
* Added custom Front End Error Page Controller (#397)
* Added Payment Data Handler (#398)
* Added Checkout Payment (#403)
* Added Direct Payment (#418)
* Added Return URLs Controller and functionality (#422)
* Added functionality for storing the payment transactions' database records (#423)
* Added Genesis Notification handler (#451)
* Added decoration to the Order Details view (#492)
* Added Reference transaction functionality (#497)
