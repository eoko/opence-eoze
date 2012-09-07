/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 30 août 2012
 */
Ext.ns('eo');

eo.metrics = {
	
	calcActionColumnWidth: function(itemsNumber, iconWidth, margin) {
		var n = Ext.isDefined(itemsNumber) ? itemsNumber : 1,
			w = (Ext.isDefined(iconWidth) ? iconWidth : 16) + 2,
			m = Ext.isDefined(margin) ? margin : 5;
		return n * (w + m) + m;
	}
	
};
