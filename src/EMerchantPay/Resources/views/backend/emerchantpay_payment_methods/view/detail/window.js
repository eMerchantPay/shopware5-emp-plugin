//{block name="backend/payment/view/main/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.EmerchantpayPaymentMethods.view.detail.Window', {
    /**
     * Override the Payment main View
     * @string
     */
    override: 'Shopware.apps.Payment.view.main.Window',

    createTabPanel: function() {
        var me = this,
            result = me.callParent();

        var snippets = {

        };

        me.emerchantpayCheckoutForm = Ext.create('Shopware.apps.EmerchantpayPaymentMethods.view.detail.EmerchantpayCheckoutForm');
        me.emerchantpayDirectForm = Ext.create('Shopware.apps.EmerchantpayPaymentMethods.view.detail.EmerchantpayDirectForm');

        result.add([{
            xtype: 'container',
            autoRender: true,
            title: '{s name=emerchantpay/config/title}emerchantpay Config{/s}',
            name: 'emerchantpay-config',
            hidden: true,
            layout: 'fit',
            region: 'center',
            autoScroll: true,
            border: 0,
            bodyBorder: false,
            defaults: {
                layout: 'fit'
            },
            items: [me.emerchantpayCheckoutForm, me.emerchantpayDirectForm]
        }]);

        return result;
    }
});
//{/block}
