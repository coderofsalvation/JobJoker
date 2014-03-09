Ext.onReady(function(){

    var workersLoading = new Ext.LoadMask(Ext.getBody(),
        {msg: "Loading Workers...", store: Ext.getCmp('WorkerCombo').store});

    var jobsLoading = window.jobsLoading = new Ext.LoadMask(Ext.getBody(),
        {msg: "Loading Jobs...", store: Ext.getCmp('JobsGrid').store});

    var workersgridLoading = new Ext.LoadMask(Ext.getBody(),
        {msg: "Loading workers...", store: Ext.getCmp('WorkersGrid').store});

    Ext.Ajax.request({
         url: "../workers/Foobar/code",
         success: function(r) {Ext.getCmp("WorkerCode").setValue(r.responseText)},
    });

    Ext.apply(Ext.form.VTypes, {
        ValidId: function(v) {
            return /[A-Za-z0-9_-]/i.test(v);
        },
        NospaceText: "Please use only alphanumeric characters, dash and underscore"
    });


    var viewport = new Ext.Viewport({
      layout: 'fit',
      renderTo: Ext.getBody(),
      items: [ 
        new Ext.TabPanel({
                activeTab: 0,
                width: "100%",
                items:[jobsGrid,jobForm,workersGrid,workerForm],
                renderTo: Ext.getBody()
        })
      ]
    });


});
