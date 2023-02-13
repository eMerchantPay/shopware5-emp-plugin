// Emerchantpay Store mode




Ext.define('Shopware.apps.EmerchantpayPaymentMethods.store.EmerchantpayTestModeStore', {
    extend: 'Ext.data.Store',

    fields: [
        { name: 'option', type: 'string' },
        { name: 'value', type: 'string' }
    ],

    data: [
        { option: '{s name="emerchantpay/config/test_mode_yes"}Yes{/s}', value: 'yes' },
        { option: '{s name="emerchantpay/config/test_mode_no"}No{/s}', value: 'no' }
    ]
});
