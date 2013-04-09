/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 oct. 2012
 */
Ext4.define('Eoze.lib.Eoze', {
	requires: [
		// adds support for lazy instance creation by xclass
		'Eoze.Ext.ComponentManager',
		// adds animOpen & animClose options
		'Eoze.Ext.window.Window',
		// i18n
		'Eoze.i18n.Locale',
		// Locale
		'Eoze.locale.fr.Locale',
		// Data types
		'Eoze.Ext.data.Types',
		// Date
		'Eoze.Ext.Date',
		// Model
		'Eoze.Ext.data.association.HasOne',

		// File upload field, fixes rendering
		'Eoze.Ext.form.field.File',

		// -- UX --
		'Ext.ux.ActivityMonitor'
	]
});
