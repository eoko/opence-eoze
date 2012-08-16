/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 16 août 2012
 */

(function() {
	
var clear = Ext.form.MessageTargets.qtip.clear;

Ext.form.MessageTargets.qtip.clear = function(field) {
	if (field.el) {
		clear.apply(this, arguments);
	}
};
	
})();
