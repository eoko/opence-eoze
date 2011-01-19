/**
 * @author Shea Frederick - http://www.vinylfox.com
 * @contributor Somani - http://www.sencha.com/forum/member.php?51567-Somani
 * @class Ext.ux.form.HtmlEditor.HeadingButtons
 * @extends Ext.ux.form.HtmlEditor.MidasCommand
 * <p>A plugin that creates a button on the HtmlEditor that will have H1 and H2 options. This is used when you want to restrict users to just a few heading types.</p>
 * NOTE: while 'heading' should be the command used, it is not supported in IE, so 'formatblock' is used instead. Thank you IE.
 */

Oce.deps.wait("Ext.ux.form.HtmlEditor.MidasCommand", function() {

Ext.ux.form.HtmlEditor.HeadingButtons = Ext.extend(Ext.ux.form.HtmlEditor.MidasCommand, {
    // private
    midasBtns: ['|', {
        enableOnSelection: true,
        cmd: 'formatblock',
        value: '<h1>',
        tooltip: {
            title: 'Titre 1'
//            title: '1st Heading'
        },
//        overflowText: '1st Heading'
        overflowText: 'Titre 1'
    }, {
        enableOnSelection: true,
        cmd: 'formatblock',
        value: '<h2>',
        tooltip: {
//            title: '2nd Heading'
            title: 'Titre 2'
        },
//        overflowText: '2nd Heading'
        overflowText: 'Titre 2'
    }]
}); 

/**
 * @author Shea Frederick - http://www.vinylfox.com
 * @class Ext.ux.form.HtmlEditor.HeadingMenu
 * @extends Ext.util.Observable
 * <p>A plugin that creates a menu on the HtmlEditor for selecting a heading size. Takes up less room than the heading buttons if your going to have all six heading sizes available.</p>
 */
Ext.ux.form.HtmlEditor.HeadingMenu = Ext.extend(Ext.util.Observable, {
    constructor: function(config) {
		Ext.ux.form.HtmlEditor.HeadingMenu.superclass.constructor.call(this, config);
		Ext.apply(this, config);
	}
	,init: function(cmp){
        this.cmp = cmp;
        this.cmp.on('render', this.onRender, this);
    }
    // private
    ,onRender: function(){
        var cmp = this.cmp,
			tb = this.cmp.getToolbar();
		var btn = {
            xtype: 'combo',
            displayField: 'display',
            valueField: 'value',
            name: 'headingsize',
            forceSelection: true,
            mode: 'local',
            triggerAction: 'all',
            width: 65,
//            emptyText: 'Heading',
            emptyText: 'Titre',
            store: {
                xtype: 'arraystore',
                autoDestroy: true,
                fields: ['value','display'],
//                data: [['H1','1st Heading'],['H2','2nd Heading'],['H3','3rd Heading'],['H4','4th Heading'],['H5','5th Heading'],['H6','6th Heading']]
                data: [['H1','Titre'],['H2','Titre 2'],['H3','Titre 3'],['H4','Titre 4'],['H5','Titre 5'],['H6','Titre 6']]
            },
            listeners: {
                'select': function(combo,rec){
                    this.relayCmd('formatblock', '<'+rec.get('value')+'>');
                    combo.reset();
                },
                scope: cmp
            }
        };

		if (this.index !== undefined) {
			if (this.pushAfter) tb.insert(this.index, this.pushAfter);
			tb.insert(this.index, btn);
		} else {
			tb.addItem(btn);
			if (this.pushAfter) tb.insert(btn);
		}
    }
});

}) // deps
