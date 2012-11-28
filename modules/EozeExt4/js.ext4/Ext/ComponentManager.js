/**
 * Adds support for lazy creation of component with option xclass:
 * 
 *     Ext4.define('My.Panel', {
 *         extend: 'Ext.Panel'
 *         ,items: [{
 *             xclass: 'My.ItemClass' // <= supported!
 *         }]
 *     });
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 oct. 2012
 */
(function(Ext) {
Ext.define('Eoze.Ext.ComponentManager', {
	override: 'Ext.ComponentManager'
	,create: function(component, defaultType){
		if (typeof component == 'string') {
			return Ext.widget(component);
		}
		if (component.isComponent) {
			return component;
		}
		// <rx>
		if (component.xclass) {
			return Ext.create(component);
		}
		// </rx>
		return Ext.widget(component.xtype || defaultType, component);
	}
});
})(Ext4);