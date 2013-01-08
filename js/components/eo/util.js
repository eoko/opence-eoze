/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 17 nov. 2011
 */
Ext.ns('eo.util');

eo.flattenArray = function(array) {
	var r = [];
	Ext.each(array, function(item) {
		if (Ext.isArray(item)) {
			r = r.concat(eo.flattenArray(item));
		} else {
			r.push(item);
		}
	});
	return r;
};
