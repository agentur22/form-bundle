pimcore.registerNS("pimcore.plugin.XxiiFormBundle");

pimcore.plugin.XxiiFormBundle = Class.create({

    initialize: function () {
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    pimcoreReady: function (e) {
        // alert("XxiiFormBundle ready!");
    }
});

var XxiiFormBundlePlugin = new pimcore.plugin.XxiiFormBundle();
