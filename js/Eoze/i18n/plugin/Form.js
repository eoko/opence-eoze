/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 oct. 2012
 */
Ext4.define('Eoze.i18n.plugin.Form', {
	
	extend: 'Ext4.AbstractPlugin'
	,alias: ['plugin.i18n.Form']
	
	,locale: {
		id: "Identifiante"
		,name: "Nombre"
		,ok: "Yeah"
		,cancel: "Nope"
		,save: "Yo!"
	}
	
	,init: function(fp) {
		debugger
		this.className = Ext4.getClassName(fp);
		Ext.each(fp.query('field'), this.localizeFieldLabels, this);
		Ext.each(fp.query('button'), this.localizeButton, this);
	}
	
	,localizeKey: function(key) {
		return this.locale[key];
	}
	
	,localizeFieldLabels: function(field) {
		var label = field.fieldLabel,
			matches = /^locale:(.+)$/.exec(label),
			key = matches && matches[1] || field.name;
		field.setFieldLabel(this.localizeKey(key) || label);
	}
	
	,localizeButton: function(button) {
		var text = button.text,
			matches = /^locale:(.+)$/.exec(text),
			key = matches && matches[1] || button.itemId,
			localText = this.localizeKey(key);
		if (localText) {
			button.setText(localText || text);
		}
	}
});
