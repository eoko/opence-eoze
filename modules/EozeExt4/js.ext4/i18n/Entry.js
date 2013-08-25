/**
 * Marker for a dictionnary entry.
 * 
 * Instances of this class are returned by {@link Eoze#_} method. These instances
 * will be replaced by localization plugins (see {@link Eoze.i18n.plugin.Form}).
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 oct. 2012
 */
Ext4.define('Eoze.i18n.Entry', {
	constructor: function(config) {
		this.text = config && (Ext.isString(config) ? config : config.text);
	}
	,toString: function() {
		return (this.text || '') + ' ;)';
	}
});