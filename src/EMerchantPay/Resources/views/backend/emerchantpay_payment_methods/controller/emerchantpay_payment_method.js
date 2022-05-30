//{block name="backend/payment/controller/payment"}
Ext.define('Shopware.apps.EmerchantpayPaymentMethods.controller.EmerchantpayPaymentMethods', {
    /**
     * Override payment Controller
     */
    override: 'Shopware.apps.Payment.controller.Payment',

    onItemClick: function(view, record) {
        var me = this
        win = view.up('window')
        tabPanel = win.tabPanel
        form = win.generalForm
        emerchantpayCheckoutForm = win.emerchantpayCheckoutForm
        emerchantpayDirectForm = win.emerchantpayDirectForm
        emerchantpayTab = win.emerchantpayCheckoutForm.up('container').tab;

        emerchantpayTab.hide();
        form.getForm().findField('name').enable();

        if (record.data.name === 'emerchantpay_checkout' || record.data.name === 'emerchantpay_direct') {
            emerchantpayTab.show();
            form.getForm().findField('name').disable();
            emerchantpayCheckoutForm.hide();
            emerchantpayDirectForm.hide();
        }

        if (record.data.name === 'emerchantpay_checkout') {
            emerchantpayCheckoutForm.show();
            emerchantpayCheckoutForm.disable();
            var checkoutStore = me.getConfigStore('checkout');
            checkoutStore.on('load', function () {
                me.normalizeTransactionTypes(checkoutStore);
                me.normalizeBankCodes(checkoutStore);
                emerchantpayCheckoutForm.loadRecord(checkoutStore.getAt(0));
                emerchantpayCheckoutForm.enable();
            });
        }

        if (record.data.name === 'emerchantpay_direct') {
            emerchantpayDirectForm.show();
            emerchantpayDirectForm.disable();
            var directStore = me.getConfigStore('direct');
            directStore.on('load', function () {
                me.normalizeTransactionTypes(directStore);
                emerchantpayDirectForm.loadRecord(directStore.getAt(0));
                emerchantpayDirectForm.enable();
            });
        }

        me.callParent(arguments);
    },

    onSavePayment: function (generalForm, countryGrid, subShopGrid, surchargeGrid) {
        var me = this
            win = generalForm.up('window');

        var emerchantpayPanel = null;
        switch (generalForm.getRecord().raw.name) {
            case 'emerchantpay_checkout':
                emerchantpayPanel = win.emerchantpayCheckoutForm;
                break;
            case 'emerchantpay_direct':
                emerchantpayPanel = win.emerchantpayDirectForm;
                break;
        }

        if (emerchantpayPanel && emerchantpayPanel.rendered) {
            if (!emerchantpayPanel.form.isValid()) {
                Shopware.Notification.createGrowlMessage(
                    '{s name=emerchantpay/config/form/title_failure}Failure{/s}',
                    generalForm.getRecord().raw.description +
                    '{s name=emerchantpay/config/form/invalid_form} can not be saved. Invalid form data.{/s}',
                    'emerchantpay'
                );
            }

            if (emerchantpayPanel.form.isValid()) {
                emerchantpayPanel.submit({
                    url: 'EmerchantpayMethodConfigs/saveConfig',
                    method: 'POST',
                    success: function(form, action) {
                        // No action for success callback
                        // Shopware shows message
                    },
                    failure: function(form, action) {
                        var message = generalForm.getRecord().raw.description +
                            '{s name=emerchantpay/config/form/error_save} error during form save.{/s} '
                            + action.result.message;
                        Shopware.Notification.createGrowlMessage(
                            '{s name=emerchantpay/config/form/title_failure}Failure{/s}',
                            message,
                            'emerchantpay'
                        );
                    }
                });
            }
        }

        me.callParent(arguments);
    },

    getConfigStore: function (method) {
        return Ext.create('Shopware.apps.EmerchantpayPaymentMethods.store.EmerchantpayConfigStore').load({
            params: {
                method: method
            }
        });
    },

    normalizeTransactionTypes: function (store) {
        store.each(function(record, index){
            var types = record.get('transaction_types');
            record.set('transaction_types[]', types);
            record.commit();
        });
    },

    normalizeBankCodes: function (store) {
        store.each(function(record, index){
            var types = record.get('bank_codes');
            record.set('bank_codes[]', types);
            record.commit();
        });
    },
});
//{/block}
