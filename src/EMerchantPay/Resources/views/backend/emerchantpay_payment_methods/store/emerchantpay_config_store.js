// Emerchantpay Config Store




Ext.define('Shopware.apps.EmerchantpayPaymentMethods.store.EmerchantpayConfigStore', {
    extend: 'Ext.data.Store',

    fields: [
        { name: 'username', type: 'string' },
        { name: 'password', type: 'string' },
        { name: 'test_mode', type: 'string' },
        { name: 'token', type: 'string' },
        { name: 'transaction_types', type: 'array' },
        { name: 'bank_codes', type: 'array' },
        { name: 'checkout_language', type: 'string' },
        { name: 'wpf_tokenization', type: 'string' }
    ],
    autoLoad: false,
    remoteSort: true,

    proxy: {
        type: 'ajax',
        url: 'EmerchantpayMethodConfigs/listConfigs',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
