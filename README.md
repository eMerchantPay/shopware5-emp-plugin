emerchantpay Gateway Module for Shopware 5
=============================

This is a Payment Module for Shopware 5, that gives you the ability to process payments through emerchantpay's Payment Gateway - Genesis.

Requirements
------------

* Shopware 5 (Tested on: __5.6.6__)
* [GenesisPHP v1.18.8](https://github.com/GenesisGateway/genesis_php/releases/tag/1.18.8)
* PCI-certified server in order to use ```emerchantpay Direct```

GenesisPHP Requirements
------------

* PHP version 5.5.9 or newer
* PHP Extensions:
    * [BCMath](https://php.net/bcmath)
    * [CURL](https://php.net/curl) (required, only if you use the curl network interface)
    * [Filter](https://php.net/filter)
    * [Hash](https://php.net/hash)
    * [XMLReader](https://php.net/xmlreader)
    * [XMLWriter](https://php.net/xmlwriter)

Installation (manual) via platform Upload Form
---------------------
* Create **zip** archive - Inside **src** directory zip EMerchantPay folder and name the archive **EMerchantPay.zip**
* Login inside the __Admin Panel__ and go to ```Configuration``` -> ```Plugin Manager```.
* Choose ```Installed``` from the left navigation panel and then on the Page with installed extensions choose ```Upload plugin```
* Choose navigate to the **EMerchantPay.zip** and upload it
* New entry with name **EMerchantPay Payment** should appear in section ```Uninstalled```. Click on the plugin and then ```Install``` and ```Activate``` it
* During the Activation process, you will be asked for cache clearing. It is required for registering all of the `emerchantpay` components. Reload the Backend after that (reload the browser page).
* Go to ```Configuration``` -> ```Payment Methods```. On the page should appear **emerchantpay Checkout** and **emerchantpay Direct**

Installation (manual) via console
---------------------
* Copy folder **EMerchantPay** and its context into **<Your Shopware root folder>\custom\plugins**
* Navigate to the root folder of your Shopware 5 installation via console and execute the following command 
    ```php ./bin/console sw:plugin:install EMerchantPay```

Configuration
---------------------
* Activate the plugin `Configuration` -> `Payment Methods`. Choose `emerchantpay Checkout` or `emerchantpay Direct` and check `Active`
* Configure the Plugin Settings. Choose `Configuration` -> `Payment Methods`. Choose `emerchantpay Checkout` or `emerchantpay Direct`. Click on `emerchantpay Config` tab and fill up the form. After that click on `Save`
* Enable Phone number. `Configuration` -> `Basic Settings`. Expand `Frontend` and choose `Login / registration`. Locate `Treat phone number as required` and set `Yes`, locate `Show phone number field` and set `Yes`. Click `Save`. This will allow your new customer to fill in a phone number on the registration page.
* Add Phone number to existing customers. `Customers` -> `Customers`. Edit the desired customer. Choose `Addresses` edit the desired address and fill in up the `Phone`

Uninstall \*CAUTION\*
---------------------
When uninstalling, a message will appear asking if the plug-in data needs to be removed:
* **Yes** - Removes all saved Plugin data \***THIS CAN NOT BE UNDONE**\*
* **No** - The Plugin data remain untouched

Supported Transactions
---------------------
* ```emerchantpay Direct``` Payment Method
	* __Authorize__
	* __Authorize (3D-Secure)__
	* __Sale__
	* __Sale (3D-Secure)__

* ```emerchantpay Checkout``` Payment Method
  * __Alternative Payment Methods__
    * __P24__
    * __POLi__
    * __PPRO__
      * __eps__
      * __GiroPay__
      * __iDEAL__
      * __MyBank__
      * __Przelewy24__
      * __SafetyPay__
      * __TrustPay__
      * __Bancontact__
    * __SOFORT__
    * __Trustly Sale__
    * __PayPal Express__
  * __Credit Cards__
    * __Account Verification__
    * __Argencard__
    * __Aura__
    * __Authorize__
    * __Authorize (3D-Secure)__
    * __Bancontact__
    * __Cabal__
    * __Cencosud__
    * __Elo__
    * __EPS__
    * __Naranja__
    * __Nativa__
    * __Sale__
    * __Sale (3D-Secure)__
    * __Tarjeta Shopping__
  * __Cash Payments__
    * __Baloto__
    * __Banamex__
    * __Banco de Occidente__
    * __Boleto__
    * __Efecty__
    * __OXXO__
    * __Pago Facil__
    * __Redpagos__
  * __Crypto__
    * __BitPay__
  * __Sepa Direct Debit__
    * __SDD Sale__
  * __Online Banking Payments__
    * __Banco do Brasil__
    * __Bancomer__
    * __Bradesco__
    * __Davivienda__
    * __Entercash__
    * __GiroPay__
    * __iDEAL__
    * __iDebit Payin__
    * __INPay__
    * __InstaDebit Payin__
    * __InstantTransfer__
    * __Itau__
    * __Multibanco__
    * __MyBank__
    * __OnlineBanking__
    * __PayU__
    * __RapiPago__
    * __Post Finance__
    * __PSE__
    * __Santander__
    * __TrustPay__
    * __UPI__
    * __Webpay__
    * __WeChat__
  * __Vouchers__
    * __CashU__
    * __Neosurf__
    * __PayByVoucher (Sale)__
    * __PaySafeCard__
  * __Gift Cards__
    * __Intersolve__
    * __Fashioncheque__
    * __Thecontainerstore__
  * __Electronic Wallets__
    * __eZeeWallet__
    * __Neteller__
    * __WebMoney__

_Note_: If you have trouble with your credentials or terminal configuration, get in touch with our [support] team

You're now ready to process payments through our gateway.

[ModMan]: https://github.com/colinmollenhour/modman
[emerchantpay Payment Gateway - Magento Connect]: https://www.magentocommerce.com/magento-connect/catalog/product/view/id/31438/s/emerchantpay-payment-gateway/
[support]: mailto:tech-support@emerchantpay.net
