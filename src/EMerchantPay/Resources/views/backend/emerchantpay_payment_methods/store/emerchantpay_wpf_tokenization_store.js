// Emerchantpay WPF Tokenization




Ext.define('Shopware.apps.EmerchantpayPaymentMethods.store.EmerchantpayWPFTokenizationStore', {
    extend: 'Ext.data.Store',

    fields: [
        { name: 'option', type: 'string' },
        { name: 'value', type: 'string' }
    ],

    data: [
        { option: "{s name=emerchantpay/config/wpf_tonkenization_yes}Yes{/s}", value: 'yes' },
        { option: "{s name=emerchantpay/config/wpf_tonkenization_no}No{/s}", value: 'no' }
    ]
});
