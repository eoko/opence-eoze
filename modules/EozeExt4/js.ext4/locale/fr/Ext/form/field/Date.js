/**
 * For some reason, the startDay of Ext.form.field.Date overrides the default
 * startDay of Ext4.picker.Date, and this is not taken into account by the
 * French local provided with official Ext release.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 oct. 2012
 */
Ext4.define('Eoze.locale.fr.Ext.form.field.Date', {
	override: 'Ext4.form.field.Date',
	startDay: 1
});
