// Emerchantpay Checkout Online banking Bank codes Store




Ext.define('Shopware.apps.EmerchantpayPaymentMethods.store.EmerchantpayCheckoutBankCodesStore', {
    extend: 'Ext.data.Store',

    fields: [
        { name: 'option', type: 'string' },
        { name: 'value', type: 'string' }
    ],
    autoLoad: false,
    remoteSort: true,

    proxy: {
        type: 'ajax',
        url: 'ConfigCheckoutCodes/listCodes',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
