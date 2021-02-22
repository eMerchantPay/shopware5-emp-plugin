//{block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.EmerchantpayTransactions.view.detail.Window', {
    /**
     * Override the Order detail window
     * @string
     */
    override: 'Shopware.apps.Order.view.detail.Window',

    createTabPanel: function() {
        var me = this,
            result = me.callParent();

        var snippets = {
            columns: {
                id: '{s name="emerchantpay/detail/id"}Id{/s}',
                transactionId: '{s name=emerchantpay/detail/transaction_id}Transaction Id{/s}',
                uniqueId: '{s name=emerchantpay/detail/unique_id}Unique Id{/s}',
                status: '{s name=emerchantpay/detail/status}Status{/s}',
                type: '{s name=emerchantpay/detail/type}Type{/s}',
                mode: '{s name=emerchantpay/detail/mode}Mode{/s}',
                amount: '{s name=emerchantpay/detail/amount}Amount{/s}',
                currency: '{s name=emerchantpay/detail/currency}Currency{/s}',
                message: '{s name=emerchantpay/detail/message}Message{/s}',
                createdAt: '{s name=emerchantpay/detail/created_at}Created At{/s}',
                updatedAt: '{s name="emerchantpay/detail/updated_at"}Updated At{/s}'
            },
            buttons: {
                capture: '{s name="emerchantpay/detail/capture"}Capture{/s}',
                void: '{s name="emerchantpay/detail/void"}Void{/s}',
                refund: '{s name="emerchantpay/detail/refund"}Refund{/s}',
                reload: '{S name="emerchantpay/detail/reload"}Reload{/s}'
            },
            messages: {
                error: '{s name="emerchantpay/detail/error"}Error{/s}',
                success: '{s name="emerchantpay/detail/success"}Success{/s}',
                ajax: {
                    error: '{s name="emerchantpay/detail/ajax_error"}Something went wrong. Please try again.{/s}'
                },
                action: {
                    success: '{s name="emerchantpay/detail/success_capture"}Successful :1.{/s}',
                    error: '{s name="emerchantpay/detail/error_capture"}Error during :1 the amount.{/s}'
                },
                data: {
                    missing: '{s name="emerchantpay/detail/missing_data"}Missing initial transaction data{/s}'
                }
            }
        };

        if (me.record && me.record.getPayment() instanceof Ext.data.Store && me.record.getPayment().first() instanceof Ext.data.Model) {
            var payment = me.record.getPayment().first();
        }

        if (payment && (payment.raw.name === 'emerchantpay_checkout' || payment.raw.name === 'emerchantpay_direct')) {
            var emerchantpayPanel = Ext.create('Shopware.apps.EmerchantpayTransactions.view.detail.Transactions', {
                title: 'emerchantpay Transactions',
                record: me.record,
                snippets: snippets,
                payment: payment
            });

            result.add([emerchantpayPanel]);
        }

        return result;
    }
});
//{/block}
