/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 15/02/11 16:55
 *
 * Overrides Ext.form.HtmlEditor.
 *
 * @cfg {Boolean} enableInsertImage (default: true)
 * @cfg {Boolean} enableHeading (default: true)
 * @cfg {Boolean} enableWordPaste (default: true)
 * 
 */
Oce.form.HtmlEditor = Ext.extend(Ext.form.HtmlEditor, {

	initComponent: function() {

		this.enableFont = false;

		var plugins = [];
		if (this.enableInsertImage !== false) {
			plugins.push(new Ext.ux.form.HtmlEditor.Image);
			Oce.form.HtmlEditor.superclass.initComponent.call(this);
		}
		if (this.enableHeading !== false) {
			plugins.push(new Ext.ux.form.HtmlEditor.HeadingMenu({
				index: 0
				,pushAfter: '-'
			}));
//			plugins.push(new Ext.ux.form.HtmlEditor.HeadingButtons);
		}
		if (this.enableWordPaste === true) {
			plugins.push(new Ext.ux.form.HtmlEditor.Word());
		}
		if (plugins.length) {
			if (!this.plugins) this.plugins = plugins;
			else {
				if (!Ext.isArray(this.plugins)) this.plugins = [this.plugins];
				this.plugins = this.plugins.concat(plugins);
			}
		}
	}

});

Ext.reg("htmleditor", 'Oce.form.HtmlEditor');