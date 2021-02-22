// Emerchantpay Checkout Transaction Types Store




Ext.define('Shopware.apps.EmerchantpayPaymentMethods.store.EmerchantpayCheckoutTransactionTypesStore', {
    extend: 'Ext.data.Store',

    fields: [
        { name: 'option', type: 'string' },
        { name: 'value', type: 'string' }
    ],
    autoLoad: false,
    remoteSort: true,

    proxy: {
        type: 'ajax',
        url: 'ConfigCheckoutTypes/listTypes',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
