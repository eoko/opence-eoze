/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 oct. 2012
 */
Ext4.define('Eoze.locale.fr.Ext.util.Format', {
	
	override: 'Ext4.util.Format'
	
	,requires: [
		// must be loaded *before*, so that localeDateFormats is not
		// overridden in the definition of Eoze.i18n.Ext.util.Format
		'Eoze.i18n.Ext.util.Format'
	]
	
	,localeDateFormats: {
		"Y-m-d": "d/m/Y"
		,"m-d": "m/Y"
		,"Y-m-d H:i:s": "d/m/Y H:i:s"
		,"H:i:s": "H:i:s"
		,"H:i": "H:i"
		,"i:s": "i:s"
	}
});