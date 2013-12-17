/**
 * Plugin that set texts in a {@link Ext.form.Panel form panel} (i.e. field labels,
 * and button texts), by using the injected {@link Eoze.i18n.Locale}.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 oct. 2012
 */
Ext4.define('Eoze.i18n.plugin.Form', {
	
	extend: 'Ext4.AbstractPlugin'
	
	,inject: ['locale']
	
	,alias: ['plugin.i18n.Form']
	
	,init: function(fp) {
		this.className = Ext4.getClassName(fp);
		Ext.each(fp.query('field'), this.localizeFieldLabels, this);
		Ext.each(fp.query('button'), this.localizeButton, this);
	}
	
	/**
	 * @private
	 */
	,localizeFieldLabels: function(field) {
		var label = field.fieldLabel,
			key = field.name;
		if (!label || label instanceof Eoze.i18n.Entry) {
			label = this.locale.translate(key, {
				tags: ['model']
			});
			if (label)  {
				field.setFieldLabel(label);
			}
		}
	}
	
	/**
	 * @private
	 */
	,localizeButton: function(button) {
		var text = button.text,
			key = button.itemId;
		if (!text || text instanceof Eoze.i18n.Entry) {
			text = this.locale.translate(key, {
				tags: ['button']
			});
			if (text)  {
				button.setText(text);
			}
		}
	}
});
