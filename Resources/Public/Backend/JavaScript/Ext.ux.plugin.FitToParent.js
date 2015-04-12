/* taken from powermail, thanks */

/* plugin for resize of grid in single container */
Ext.namespace('Ext.ux.plugin');
Ext.ux.plugin.FitToParent = Ext.extend(Object, {
    constructor: function (parent) {
        this.parent = parent;
    },
    init: function (c) {
        c.on('render', function (c) {
            c.fitToElement = Ext.get(this.parent
            || c.getPositionEl().dom.parentNode);
            if (!c.doLayout) {
                this.fitSizeToParent();
                Ext.EventManager.onWindowResize(this.fitSizeToParent, this);
            }
        }, this, {
            single: true
        });
        if (c.doLayout) {
            c.monitorResize = true;
            c.doLayout = c.doLayout.createInterceptor(this.fitSizeToParent);
        }
    },
    fitSizeToParent: function () {
        // Uses the dimension of the current viewport, but removes the document header
        // and an additional margin of 40 pixels (e.g. Safari needs this addition)

        var bodyHeight = Ext.getBody().getHeight();

        if (Ext.get('typo3-docbody') && Ext.get('typo3-docbody').getHeight() >= bodyHeight) {
            bodyHeight = Ext.get('typo3-docbody').getHeight();
        }

        this.fitToElement.setHeight(bodyHeight - this.fitToElement.getTop() - 40);
        var pos = this.getPosition(true), size = this.fitToElement.getViewSize();
        var width = size.width - pos[0];
        var height = size.height - pos[1];

        if (width <= 400) {
            width = 400;
        }

        if (height <= 400) {
            height = 400;
        }

        this.setSize(width, height);

    }
});
