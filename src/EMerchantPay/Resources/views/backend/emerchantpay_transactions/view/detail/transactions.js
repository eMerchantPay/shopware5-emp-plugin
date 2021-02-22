// This tab will be shown in the order module



Ext.define('Shopware.apps.EmerchantpayTransactions.view.detail.Transactions', {
    name: 'emerchantpay-order-transactions',
    extend: 'Ext.container.Container',
    padding: 10,
    title: 'emerchantpayTransactions',

    initComponent: function () {
        var me = this;

        var myStore = this.transactionsStore().load();

        me.gridPanel = Ext.create('Ext.grid.Panel', {
            cmpTransactions: me,
            record: me.record,
            store: myStore,
            columns: me.getColumns(),
            buttons: [
                { minWidth: 20, iconCls: 'x-tbar-loading',  handler: me.onReload},
                { text: me.snippets.buttons.capture, handler: me.onCaptureEvent },
                { text: me.snippets.buttons.refund, handler: me.onRefundEvent },
                { text: me.snippets.buttons.void, handler: me.onCancelEvent }
            ],
            border: false
        });

        me.items = [me.gridPanel];

        me.callParent(arguments);
    },

    onReload: function (button) {
        var grid = button.up('grid');
        grid.getStore().reload();
    },

    onCaptureEvent: function (button) {
        var gridPanel = button.up('grid');

        gridPanel.cmpTransactions.createReferenceAction('capture', button);
    },

    onRefundEvent: function (button) {
        var gridPanel = button.up('grid');

        gridPanel.cmpTransactions.createReferenceAction('refund', button);
    },

    onCancelEvent: function (button) {
        var gridPanel = button.up('grid');

        gridPanel.cmpTransactions.createReferenceAction('void', button);
    },

    transactionsStore: function () {
        var me = this;

        return Ext.define('Shopware.apps.Emerchantpay.store.EmerchantpayTransactions', {
            extend: 'Ext.data.Store',
            fields: [
                { name:'id', type: 'int' },
                { name:'transaction_id', type: 'string' },
                { name:'unique_id', type: 'string' },
                { name:'status', type: 'string' },
                { name:'type', type: 'string' },
                { name:'mode', type: 'string' },
                { name:'amount', type: 'string' },
                { name:'currency', type: 'string' },
                { name:'message', type: 'string' },
                { name:'created_at', type: 'string' },
                { name:'updated_at', type: 'string' }
            ],
            autoLoad: false,
            remoteSort: true,
            pageSize: 100,
            proxy: {
                type: 'ajax',
                url: 'EmerchantpayTransactions/list?orderId=' + me.record.data.number,
                reader: {
                    type: 'json',
                    root: 'data',
                    totalProperty: 'total'
                }
            }
        }).create();
    },

    getColumns: function () {
        return [
            {
                text: this.snippets.columns.id,
                dataIndex: 'id',
                menuDisabled: true,
                width: 40,
                sortable: false
            },
            {
                text: this.snippets.columns.transactionId,
                dataIndex: 'transaction_id',
                width: 200,
                menuDisabled: true,
                sortable: false
            },
            {
                text: this.snippets.columns.uniqueId,
                dataIndex: 'unique_id',
                width: 220,
                menuDisabled: true,
                sortable: false
            },
            {
                text: this.snippets.columns.status,
                dataIndex: 'status',
                width: 50,
                menuDisabled: true,
                sortable: false
            },
            {
                text: this.snippets.columns.type,
                dataIndex: 'type',
                width: 100,
                menuDisabled: true,
                sortable: false
            },
            {
                text: this.snippets.columns.mode,
                dataIndex: 'mode',
                width: 80,
                menuDisabled: true,
                sortable: false
            },
            {
                text: this.snippets.columns.amount,
                dataIndex: 'amount',
                width: 80,
                menuDisabled: true,
                sortable: false
            },
            {
                text: this.snippets.columns.currency,
                dataIndex: 'currency',
                width: 50,
                menuDisabled: true
            },
            {
                text: this.snippets.columns.message,
                dataIndex: 'message',
                width: 200,
                menuDisabled: true,
                sortable: false
            },
            {
                text: this.snippets.columns.createdAt,
                dataIndex: 'created_at',
                xtype:'datecolumn',
                format:'Y/m/d H:i',
                width: 100,
                menuDisabled: true,
                sortable: false
            },
            {
                text: this.snippets.columns.updatedAt,
                dataIndex: 'updated_at',
                xtype:'datecolumn',
                format:'Y/m/d H:i',
                width: 100,
                menuDisabled: true,
                sortable: false
            }
        ]
    },

    createReferenceAction: function (action, button) {
        var me = this;

        button.disable();

        var transactionData = this.gridPanel.cmpTransactions.getTransactionData(action);

        if (!transactionData) {
            return false;
        }

        this.gridPanel.cmpTransactions.sendReferenceRequest(
            transactionData,
            function (result) {
                me.gridPanel.cmpTransactions.processResult(result);
                me.gridPanel.getStore().reload();
            },
            function () {
                button.enable();
            }
        );

        return true;
    },

    getTransactionData: function (action) {
        var transactionData = this.extractInitialTransactionData(this.gridPanel.getStore().data.items);

        if (transactionData.length === 0) {
            Shopware.Notification.createGrowlMessage(
                this.snippets.messages.error,
                this.snippets.messages.data.missing,
                'emerchantpay'
            );
            return false;
        }

        // Get the Transaction Id assigned to the Order Object
        transactionData[0].transactionAction = action;
        transactionData[0].transactionId = this.record.data.transactionId;
        transactionData[0].paymentMethod = this.payment.raw.name;

        return transactionData;
    },

    extractInitialTransactionData: function (items) {
        if (items.length === 0) {
            return [];
        }

        return [{
            orderId: items[0].raw.order_id,
            paymentToken: items[0].raw.payment_token
        }];
    },

    sendReferenceRequest: function (data, success, always) {
        var me = this;

        Ext.Ajax.request({
            url: 'EmerchantpayReferenceTransaction/reference_transaction',
            params: data[0],
            success: function(response, opts) {
                opts.always();

                try{
                    success(Ext.decode(response.responseText));
                }
                catch(err) {
                    var message = (err.hasOwnProperty('msg')) ?
                        err.msg.slice(0, 255) : me.snippets.messages.ajax.error;

                    Shopware.Notification.createGrowlMessage(
                        me.snippets.messages.error,
                        message,
                        'emerchantpay',
                        'sprite-minus-circle-frame',
                        true
                    );

                    return false;
                }
            },
            failure: function(response, opts) {
                opts.always();

                Shopware.Notification.createGrowlMessage(
                    me.snippets.messages.error,
                    me.snippets.messages.ajax.error,
                    'emerchantpay',
                    'sprite-cross-circle'
                );

                return false;
            },
            always: function () {
                always();
            }
        });
    },

    processResult: function(result) {
        switch (result.status) {
            case 'error':
            case 'declined':
                Shopware.Notification.createGrowlMessage(
                    this.snippets.messages.error,
                    this.snippets.messages.action.error.replace(
                        new RegExp(':1', 'i'),
                        result.action
                    ) + '</br>' + result.message,
                    'emerchantpay',
                    'sprite-cross-circle'
                );
                break;
            case 'approved':
                Shopware.Notification.createGrowlMessage(
                    this.snippets.messages.success,
                    this.snippets.messages.action.success.replace(
                        new RegExp(':1', 'i'), result.action
                    ) + '</br>' + result.message,
                    'emerchantpay',
                    'sprite-tick'
                );
                break;
        }
    }
});
