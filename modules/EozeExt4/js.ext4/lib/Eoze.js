//noinspection JSLastCommaInArrayLiteral
/**
 * Eoze for ExtJS4 standard library. In essence, this class requires all standard Eoze classes.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 oct. 2012
 */
Ext4.define('Eoze.lib.Eoze', {
	requires: [
		// Ext4 Fixes
		'Eoze.fix.FixesLoader',
		// Ext4 Overrides
		'Eoze.Ext.OverridesLoader',

		// Deft
		'Eoze.Deft.Overrides',
		// i18n
		'Eoze.i18n.Locale',
		// Locale
		'Eoze.locale.fr.Locale',
		// Proxy
		'Eoze.data.proxy.GridModule',
		'Eoze.data.proxy.Cqlix',
		'Eoze.data.proxy.SlaveMemory',
		// Types
		'Eoze.data.type.HasOne',
		// CkEditor
		'Eoze.CkEditor.form.field.CkEditor',
		// Cursor
		'Eoze.Cursor',
		// Plugins
		'Eoze.form.field.mixin.DependsOnCheckbox',

		'Eoze.grid.column.DateTime',
		'Eoze.grid.column.Integer',

		// -- UX --
		'Ext.ux.ActivityMonitor',
		'Ext.ux.Eoze.HttpCache'
	]
});
