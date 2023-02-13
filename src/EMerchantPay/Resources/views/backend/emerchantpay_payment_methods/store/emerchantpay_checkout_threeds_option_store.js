// Emerchantpay Threeds Option




Ext.define('Shopware.apps.EmerchantpayPaymentMethods.store.EmerchantpayCheckoutThreedsOptionStore', {
    extend: 'Ext.data.Store',

    fields: [
        { name: 'option', type: 'string' },
        { name: 'value', type: 'string' }
    ],

    data: [
        { option: '{s name="emerchantpay/config/threeds_option_yes"}Yes{/s}', value: 'yes' },
        { option: '{s name="emerchantpay/config/threeds_option_no"}No{/s}', value: 'no' }
    ]
});
