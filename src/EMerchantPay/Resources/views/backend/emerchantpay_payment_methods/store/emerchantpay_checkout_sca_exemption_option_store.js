// Emerchantpay SCA Exemption option




Ext.define('Shopware.apps.EmerchantpayPaymentMethods.store.EmerchantpayCheckoutScaExemptionOptionStore', {
    extend: 'Ext.data.Store',

    fields: [
        { name: 'option', type: 'string' },
        { name: 'value', type: 'string' }
    ],

    data: [
        { option: '{s name="emerchantpay/config/sca_exemption_option_low_value"}Low value{/s}', value: 'low_value' },
        { option: '{s name="emerchantpay/config/sca_exemption_option_low_risk"}Low risk{/s}', value: 'low_risk' },
    ]
});
