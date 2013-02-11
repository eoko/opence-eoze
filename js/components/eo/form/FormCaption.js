/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 23 mai 2012
 */
(function() {
	
Ext.ns('eo.form');

/**
 * Help caption to be used as help 
 *
 * @xtype formhelp
 */
eo.form.FormCaption = Ext.extend(Ext.BoxComponent, {
	
	cls: 'form-caption'
	
	/**
	 * @cfg {String} text
	 * 
	 * Help text to display in the caption. The text will be wrapped in a `<p>` element.
	 * If you don't want that or if you need a more complex HTML layout, use the
	 * {@link #html} option.
	 * 
	 * If the {@link #html} option is set, then this text will be ignored.
	 */
	,text: null
	
	,initComponent: function() {
		
		if (this.text && !this.html) {
			// ext3.4 String.format does not exists in ext4+
			if (/^\s*<p>/.test(this.text)) {
				this.html = this.text;
			} else {
				this.html = String.format("<p>{0}</p>", this.text);
			}
		}
		
		this.callParent();
	}
});

Ext.reg('formcaption', 'eo.form.FormCaption');

})(); // closure