// Emerchantpay SCA Exemption option amount




Ext.define('Shopware.apps.EmerchantpayPaymentMethods.store.EmerchantpayCheckoutScaExemptionAmountStore', {
    extend: 'Ext.data.Store',

    fields: [
        { name: 'option', type: 'string' },
        { name: 'value', type: 'int' }
    ]
});
